<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class BhwLeastPrivilegeTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    private function createZoneAssignedBhw(int $zoneId = 1, array $extraPermissions = []): User
    {
        $user = $this->createUserWithNamedRole('BHW', array_merge(
            ['consultations', 'immunizations', 'maternal', 'reports', 'print_handouts', 'dashboard_handouts'],
            $extraPermissions
        ));

        $workerId = DB::table('health_workers')->insertGetId([
            'user_id' => $user->id,
            'first_name' => 'Zone',
            'last_name' => 'BHW',
            'role' => 'BHW',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('zones')->updateOrInsert(
            ['id' => $zoneId],
            ['zone_number' => (string) $zoneId, 'assigned_worker_id' => $workerId]
        );

        return $user;
    }

    private function createPatientInZone(int $zoneId): int
    {
        $householdId = DB::table('households')->insertGetId([
            'zone_id' => $zoneId,
            'head_last_name' => 'Test Family',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('patients')->insertGetId([
            'household_id' => $householdId,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'sex' => 'Female',
            'date_of_birth' => '1990-01-01',
            'civil_status' => 'Single',
            'employment_status' => 'Employed',
            'mother_name' => 'Jane Senior',
            'spouse_name' => 'N/A',
            'family_relationship' => 'Mother',
            'residential_address' => 'Sta. Ana, Tagoloan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function bhw_cannot_access_patient_registry(): void
    {
        $bhw = $this->createZoneAssignedBhw();
        $this->actingAs($bhw);

        $response = $this->get('/patients');
        $response->assertStatus(403);
    }

    /** @test */
    public function bhw_cannot_access_household_pages(): void
    {
        $bhw = $this->createZoneAssignedBhw();
        $this->actingAs($bhw);

        $this->get('/households')->assertStatus(403);
        $this->get('/households/create')->assertStatus(403);
        $this->post('/households', [])->assertStatus(403);
    }

    /** @test */
    public function bhw_can_access_create_patient_form(): void
    {
        $bhw = $this->createZoneAssignedBhw();
        $this->actingAs($bhw);

        $response = $this->get('/patients/create');
        $response->assertStatus(200);
    }

    /** @test */
    public function bhw_can_register_patient_and_redirects_to_show(): void
    {
        $bhw = $this->createZoneAssignedBhw(1);
        $this->actingAs($bhw);

        $payload = [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'middle_name' => '',
            'sex' => 'Male',
            'date_of_birth' => '1995-01-01',
            'civil_status' => 'Single (Walang Asawa)',
            'employment_status' => 'Employed',
            'mother_name' => 'Maria Cruz',
            'spouse_name' => 'N/A',
            'family_relationship' => 'Father',
            'residential_address' => 'Zone 1, Sta. Ana',
            'is_philhealth_member' => 'n',
            'is_pcb_member' => 'n',
            'create_new_household' => 1,
            'new_household_head_first_name' => 'Juan',
            'new_household_head_last_name' => 'Dela Cruz',
            'new_household_zone_id' => 1,
            'new_household_contact_number' => '09123456789',
        ];

        $response = $this->post('/patients', $payload);

        $response->assertStatus(302);
        $this->assertStringContainsString('/patients/', $response->headers->get('Location'));

        $this->assertDatabaseHas('patients', [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
        ]);
    }

    /** @test */
    public function bhw_can_search_patients_and_households(): void
    {
        $bhw = $this->createZoneAssignedBhw(1);
        $this->actingAs($bhw);

        $this->createPatientInZone(1);

        $this->getJson('/search/patients?query=Doe')->assertOk()->assertJsonCount(1);
        $this->getJson('/search/households?query=Test')->assertOk()->assertJsonCount(1);
    }

    /** @test */
    public function bhw_can_open_consultation_modal_and_save(): void
    {
        $bhw = $this->createZoneAssignedBhw(1);
        $patientId = $this->createPatientInZone(1);
        $this->actingAs($bhw);

        $response = $this->getJson("/patients/{$patientId}/consultations/create");
        $response->assertOk();

        $consultationPayload = [
            'mode_of_transaction' => 'Walk-in',
            'nature_of_visit' => 'Follow-up',
            'purpose_of_visit' => 'Routine check-up',
            'chief_complaint' => 'Fever',
            'bp_systolic' => 120,
            'bp_diastolic' => 80,
            'temperature' => 37.8,
            'weight' => 65,
            'height' => 165,
        ];

        $response = $this->postJson("/patients/{$patientId}/consultations", $consultationPayload);
        $response->assertStatus(302);
        $this->assertStringContainsString('/consultations/', $response->headers->get('Location'));
    }

    /** @test */
    public function bhw_cannot_view_out_of_zone_patient(): void
    {
        $bhw = $this->createZoneAssignedBhw(1);
        $this->createZone(2);
        $outOfZonePatient = $this->createPatientInZone(2);
        $this->actingAs($bhw);

        $response = $this->get("/patients/{$outOfZonePatient}");
        $response->assertStatus(403);
    }

    /** @test */
    public function bhw_can_view_in_zone_patient(): void
    {
        $bhw = $this->createZoneAssignedBhw(1);
        $inZonePatient = $this->createPatientInZone(1);
        $this->actingAs($bhw);

        $response = $this->get("/patients/{$inZonePatient}");
        $response->assertStatus(200);
    }

    /** @test */
    public function bhw_cannot_create_consultation_for_out_of_zone_patient(): void
    {
        $bhw = $this->createZoneAssignedBhw(1);
        $this->createZone(2);
        $outOfZonePatient = $this->createPatientInZone(2);
        $this->actingAs($bhw);

        $consultationPayload = [
            'mode_of_transaction' => 'Walk-in',
            'nature_of_visit' => 'Follow-up',
            'purpose_of_visit' => 'Routine check-up',
            'chief_complaint' => 'Fever',
            'bp_systolic' => 120,
            'bp_diastolic' => 80,
            'temperature' => 37.8,
            'weight' => 65,
            'height' => 165,
        ];

        $response = $this->postJson("/patients/{$outOfZonePatient}/consultations", $consultationPayload);
        $response->assertStatus(403);
    }

    private function createZone(int $zoneId): void
    {
        DB::table('zones')->updateOrInsert(
            ['id' => $zoneId],
            ['zone_number' => (string) $zoneId, 'assigned_worker_id' => null]
        );
    }

    /** @test */
    public function bhw_cannot_delete_patient(): void
    {
        $bhw = $this->createZoneAssignedBhw(1);
        $patient = $this->createPatientInZone(1);
        $this->actingAs($bhw);

        $response = $this->delete("/patients/{$patient}");
        $response->assertStatus(403);
    }

    /** @test */
    public function is_zone_scoped_is_true_for_bhw_role_without_household_permission(): void
    {
        $bhw = $this->createUserWithPermissions(['consultations']);
        $bhw->role()->associate(Role::where('role_name', 'BHW')->first())->save();
        $bhw->load('role');

        $this->assertTrue($bhw->isZoneScoped());
    }

    /** @test */
    public function is_zone_scoped_is_false_for_nurse_role(): void
    {
        $nurse = $this->createUserWithPermissions(['patients', 'consultations', 'medicines']);
        $nurse->role()->associate(Role::where('role_name', 'Nurse')->first())->save();
        $nurse->load('role');

        $this->assertFalse($nurse->isZoneScoped());
    }

    /** @test */
    public function migration_removes_household_and_patients_from_bhw(): void
    {
        $bhwRole = Role::where('role_name', 'BHW')->first();
        $this->assertNotNull($bhwRole);

        $permIds = Permission::whereIn('name', ['household', 'patients'])->pluck('id');
        $bhwRole->permissions()->sync($permIds);

        $migration = include database_path('migrations/2026_09_30_000001_remove_household_and_patients_from_bhw_role.php');
        $migration->up();

        $bhwRole->refresh();
        $this->assertFalse($bhwRole->permissions->contains('name', 'household'));
        $this->assertFalse($bhwRole->permissions->contains('name', 'patients'));
    }

    /** @test */
    public function migration_down_restores_household_and_patients(): void
    {
        // Ensure permissions exist
        Permission::updateOrCreate(['name' => 'household'], ['description' => 'Access to Household module']);
        Permission::updateOrCreate(['name' => 'patients'], ['description' => 'Access to Patients module']);

        $bhwRole = Role::where('role_name', 'BHW')->first();
        $this->assertNotNull($bhwRole);

        $migration = include database_path('migrations/2026_09_30_000001_remove_household_and_patients_from_bhw_role.php');
        $migration->down();

        $bhwRole->refresh();
        $this->assertTrue($bhwRole->permissions->contains('name', 'household'));
        $this->assertTrue($bhwRole->permissions->contains('name', 'patients'));
    }

    /** @test */
    public function migration_2026_09_30_000002_strips_household_and_patients_from_bhw(): void
    {
        $bhwRole = Role::where('role_name', 'BHW')->first();
        $this->assertNotNull($bhwRole);

        Permission::updateOrCreate(['name' => 'household'], ['description' => 'Access to Household module']);
        Permission::updateOrCreate(['name' => 'patients'], ['description' => 'Access to Patients module']);
        Permission::updateOrCreate(['name' => 'consultations'], ['description' => 'Access to Consultations module']);

        $bhwRole->permissions()->sync(
            Permission::whereIn('name', ['household', 'patients', 'consultations'])->pluck('id')
        );

        $migration = include database_path('migrations/2026_09_30_000002_add_patients_destroy_permission.php');
        $migration->up();

        $bhwRole->refresh();
        $this->assertFalse($bhwRole->permissions->contains('name', 'household'));
        $this->assertFalse($bhwRole->permissions->contains('name', 'patients'));
        $this->assertFalse($bhwRole->permissions->contains('name', 'patients_destroy'));
        $this->assertTrue($bhwRole->permissions->contains('name', 'consultations'));

        foreach (['Admin', 'Doctor', 'Nurse', 'Midwife'] as $roleName) {
            $role = Role::where('role_name', $roleName)->first();
            $this->assertNotNull($role);
            $this->assertTrue($role->permissions->contains('name', 'patients_destroy'));
        }
    }

    /** @test */
    public function bhw_with_drifted_patients_permission_cannot_delete_restore_or_force_delete(): void
    {
        // Simulates the live-DB drift where BHW still holds `patients`.
        $bhw = $this->createZoneAssignedBhw(1, ['patients']);
        $patientId = $this->createPatientInZone(1);

        $this->actingAs($bhw);

        $this->delete("/patients/{$patientId}")->assertStatus(403);
        $this->assertNotSoftDeleted('patients', ['id' => $patientId]);

        DB::table('patients')->where('id', $patientId)->update(['deleted_at' => now()]);

        $this->post("/patients/{$patientId}/restore")->assertStatus(403);
        $this->assertSoftDeleted('patients', ['id' => $patientId]);

        $this->delete("/patients/{$patientId}/force")->assertStatus(403);
        $this->assertDatabaseHas('patients', ['id' => $patientId]);
    }

    /** @test */
    public function bhw_patient_profile_hides_archive_and_permanent_delete_actions(): void
    {
        $bhw = $this->createZoneAssignedBhw(1);
        $patientId = $this->createPatientInZone(1);

        $response = $this->actingAs($bhw)->get("/patients/{$patientId}");

        $response->assertStatus(200);
        $response->assertDontSee('Data Management');
        $response->assertDontSee('Archive Patient');
        $response->assertDontSee('Delete Permanently');
    }

    /** @test */
    public function patient_profile_shows_archive_and_permanent_delete_actions_with_patients_destroy(): void
    {
        $this->createZone(1);
        $user = $this->createUserWithPermissions(['patients', 'patients_destroy']);
        $patientId = $this->createPatientInZone(1);

        $response = $this->actingAs($user)->get("/patients/{$patientId}");

        $response->assertStatus(200);
        $response->assertSee('Data Management');
        $response->assertSee('Archive Patient');
        $response->assertSee('Delete Permanently');
    }
}
