<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Consultation;
use App\Models\HealthWorker;
use App\Models\Patient;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class AuditLoggingTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    private function actingUser(): User
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        return $user;
    }

    private function createPatient(User $user): Patient
    {
        $zone = Zone::query()->create(['zone_number' => '1']);

        $householdId = DB::table('households')->insertGetId([
            'zone_id' => $zone->id,
            'head_last_name' => 'Dela Cruz',
        ]);

        return Patient::query()->create([
            'household_id' => $householdId,
            'last_name' => 'Dela Cruz',
            'first_name' => 'Juan',
            'middle_name' => null,
            'suffix' => null,
            'sex' => 'Male',
            'date_of_birth' => '1990-01-01',
            'birth_place' => 'Sta. Ana',
            'blood_type' => 'O+',
            'civil_status' => 'Single',
            'educational_attainment' => 'College',
            'employment_status' => 'Employed',
            'mother_name' => 'Maria',
            'spouse_name' => 'Mercedes',
            'family_relationship' => 'Son',
            'residential_address' => 'Zone 1',
            'is_philhealth_member' => 'n',
            'status_type' => null,
            'philhealth_no' => null,
            'membership_category' => null,
            'is_pcb_member' => 'n',
            'has_4ps' => false,
            'has_nhts' => false,
        ]);
    }

    private function createHealthWorker(User $user): HealthWorker
    {
        return HealthWorker::query()->create([
            'user_id' => $user->id,
            'first_name' => 'Nurse',
            'last_name' => 'Bacani',
            'role' => 'Nurse',
        ]);
    }

    public function test_patient_creation_logs_exactly_one_audit_entry(): void
    {
        $this->actingUser();

        $patient = $this->createPatient($this->actingUser());

        $this->assertSame(1, AuditLog::query()
            ->where('table_name', 'patients')
            ->where('record_id', $patient->id)
            ->where('action', 'created')
            ->count());
    }

    public function test_patient_update_logs_exactly_one_audit_entry(): void
    {
        $this->actingUser();

        $patient = $this->createPatient($this->actingUser());

        $patient->update(['first_name' => 'Pedro']);

        $this->assertSame(1, AuditLog::query()
            ->where('table_name', 'patients')
            ->where('record_id', $patient->id)
            ->where('action', 'updated')
            ->count());
    }

    public function test_user_creation_logs_exactly_one_audit_entry(): void
    {
        $this->actingUser();

        $user = User::factory()->create();

        $this->assertSame(1, AuditLog::query()
            ->where('table_name', 'users')
            ->where('record_id', $user->id)
            ->where('action', 'created')
            ->count());
    }

    public function test_user_update_logs_exactly_one_audit_entry(): void
    {
        $this->actingUser();

        $user = User::factory()->create();

        $user->update(['username' => 'updated_username']);

        $this->assertSame(1, AuditLog::query()
            ->where('table_name', 'users')
            ->where('record_id', $user->id)
            ->where('action', 'updated')
            ->count());
    }

    public function test_consultation_creation_logs_exactly_one_audit_entry(): void
    {
        $user = $this->actingUser();

        $patient = $this->createPatient($user);
        $worker = $this->createHealthWorker($user);

        $consultation = Consultation::query()->create([
            'patient_id' => $patient->id,
            'worker_id' => $worker->id,
            'status' => 'triage',
            'is_locked' => false,
            'mode_of_transaction' => 'walk-in',
        ]);

        $this->assertSame(1, AuditLog::query()
            ->where('table_name', 'consultations')
            ->where('record_id', $consultation->id)
            ->where('action', 'created')
            ->count());
    }

    public function test_consultation_update_logs_exactly_one_audit_entry(): void
    {
        $user = $this->actingUser();

        $patient = $this->createPatient($user);
        $worker = $this->createHealthWorker($user);

        $consultation = Consultation::query()->create([
            'patient_id' => $patient->id,
            'worker_id' => $worker->id,
            'status' => 'triage',
            'is_locked' => false,
            'mode_of_transaction' => 'walk-in',
        ]);

        $consultation->update(['status' => 'completed']);

        $this->assertSame(1, AuditLog::query()
            ->where('table_name', 'consultations')
            ->where('record_id', $consultation->id)
            ->where('action', 'updated')
            ->count());
    }

    public function test_noop_update_does_not_log_audit_entry(): void
    {
        $this->actingUser();

        $patient = $this->createPatient($this->actingUser());

        $patient->update(['first_name' => 'Juan']);

        $this->assertSame(0, AuditLog::query()
            ->where('table_name', 'patients')
            ->where('record_id', $patient->id)
            ->where('action', 'updated')
            ->count());
    }

    public function test_update_entry_stores_only_changed_fields(): void
    {
        $this->actingUser();

        $patient = $this->createPatient($this->actingUser());

        $patient->update(['first_name' => 'Pedro']);

        $log = AuditLog::query()
            ->where('table_name', 'patients')
            ->where('record_id', $patient->id)
            ->where('action', 'updated')
            ->first();

        $this->assertNotNull($log);
        $this->assertArrayHasKey('first_name', $log->old_values);
        $this->assertSame('Juan', $log->old_values['first_name']);
        $this->assertSame('Pedro', $log->new_values['first_name']);
        $this->assertArrayNotHasKey('last_name', $log->old_values);
        $this->assertArrayNotHasKey('date_of_birth', $log->old_values);
        $this->assertCount(1, $log->old_values);
    }

    public function test_entries_carry_taxonomy_category_and_severity(): void
    {
        $this->actingUser();

        $patient = $this->createPatient($this->actingUser());

        $createLog = AuditLog::query()
            ->where('table_name', 'patients')
            ->where('record_id', $patient->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($createLog);
        $this->assertSame('records', $createLog->category);
        $this->assertSame('info', $createLog->severity);

        $user = User::factory()->create();

        $userLog = AuditLog::query()
            ->where('table_name', 'users')
            ->where('record_id', $user->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($userLog);
        $this->assertSame('admin', $userLog->category);
    }

    public function test_failed_login_entry_is_classified_as_auth_error(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.process'), [
            'username' => $user->username,
            'password' => 'wrong-password',
        ]);

        $log = AuditLog::query()
            ->where('action', 'login_failed')
            ->latest('created_at')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('auth', $log->category);
        $this->assertSame('error', $log->severity);
    }

    public function test_death_store_logs_exactly_one_audit_entry(): void
    {
        $user = $this->createUserWithPermissions(['patients']);
        $this->actingAs($user);

        $zone = Zone::query()->create(['zone_number' => '1']);
        $householdId = DB::table('households')->insertGetId([
            'zone_id' => $zone->id,
            'head_last_name' => 'Cruz',
        ]);
        $patient = Patient::query()->create([
            'household_id' => $householdId,
            'last_name' => 'Cruz',
            'first_name' => 'Maria',
            'middle_name' => null,
            'suffix' => null,
            'sex' => 'Male',
            'date_of_birth' => '1960-01-01',
            'birth_place' => 'Sta. Ana',
            'blood_type' => 'O+',
            'civil_status' => 'Married',
            'educational_attainment' => 'High school',
            'employment_status' => 'Employed',
            'mother_name' => 'Ana',
            'spouse_name' => null,
            'family_relationship' => 'Spouse',
            'residential_address' => 'Zone 1',
            'is_philhealth_member' => 'n',
            'status_type' => null,
            'philhealth_no' => null,
            'membership_category' => null,
            'is_pcb_member' => 'n',
            'has_4ps' => false,
            'has_nhts' => false,
        ]);

        $worker = HealthWorker::query()->create([
            'user_id' => $user->id,
            'first_name' => 'Nurse',
            'last_name' => 'Santos',
            'role' => 'Nurse',
        ]);

        $this->post(route('deaths.store', $patient), [
            'date_of_death' => now()->toDateString(),
            'place_of_death' => 'home',
            'cause_text' => 'Sepsis',
            'recorded_by' => $worker->id,
        ])->assertSessionHas('success');

        $this->assertSame(1, AuditLog::query()
            ->where('table_name', 'deaths')
            ->where('action', 'created')
            ->count());
        $this->assertSame(0, AuditLog::query()
            ->where('table_name', 'deaths')
            ->where('action', 'death_created')
            ->count());
    }

    public function test_medicine_import_logs_audit_entry_with_counts(): void
    {
        $user = $this->createUserWithPermissions(['medicines']);
        $this->actingAs($user);

        $csv = "name,form\nParacetamol,Tablet\nAmoxicillin,Capsule\n";

        $this->post(route('medicines.import'), [
            'csv_file' => UploadedFile::fake()->createWithContent('medicines.csv', $csv),
        ])->assertSessionHas('success');

        $log = AuditLog::query()
            ->where('table_name', 'medicines_lookup')
            ->where('action', 'medicines_imported')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame(2, $log->new_values['success_count']);
        $this->assertSame(0, $log->new_values['error_count']);
    }

    public function test_medicine_import_fatal_error_logs_audit_entry(): void
    {
        $this->actingAs($this->createUserWithPermissions(['medicines']));

        $this->post(route('medicines.import'), [
            'csv_file' => UploadedFile::fake()->createWithContent('medicines.csv', ''),
        ]);

        $this->assertSame(1, AuditLog::query()
            ->where('table_name', 'medicines_lookup')
            ->where('action', 'medicines_import_failed')
            ->count());
    }

    public function test_role_update_logs_audit_entry_with_permission_changes(): void
    {
        $admin = $this->createUserWithPermissions(['users']);
        $this->actingAs($admin);

        $roleId = $this->createRoleWithPermissions(['patients']);

        $extraPermissionId = DB::table('permissions')->insertGetId([
            'name' => 'consultations',
            'description' => 'Test permission: consultations',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $oldPermissionIds = DB::table('role_permissions')
            ->where('role_id', $roleId)
            ->pluck('permission_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->put(route('roles.update', $roleId), [
            'role_name' => 'Nurse Updated',
            'permissions' => [$extraPermissionId],
        ])->assertRedirect(route('roles.index'));

        $log = AuditLog::query()
            ->where('table_name', 'user_roles')
            ->where('record_id', $roleId)
            ->where('action', 'role_updated')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame($oldPermissionIds, $log->old_values['permission_ids']);
        $this->assertSame([$extraPermissionId], $log->new_values['permission_ids']);
    }

    public function test_user_audit_entries_never_store_password_hash(): void
    {
        $this->actingUser();

        $user = User::factory()->create(['password' => bcrypt('secret123')]);

        $createdLog = AuditLog::query()
            ->where('table_name', 'users')
            ->where('record_id', $user->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($createdLog);
        $this->assertArrayNotHasKey('password', $createdLog->new_values);
        $this->assertStringNotContainsString(
            'secret123',
            json_encode($createdLog->new_values)
        );
    }
}
