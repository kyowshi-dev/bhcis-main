<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class BhwCrossZoneEnrollmentTest extends TestCase
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

    private function createZone(int $id): void
    {
        DB::table('zones')->updateOrInsert(
            ['id' => $id],
            ['zone_number' => (string) $id, 'assigned_worker_id' => null]
        );
    }

    private function createHouseholdInZone(int $zoneId, string $familyNameHead): int
    {
        return DB::table('households')->insertGetId([
            'zone_id' => $zoneId,
            'head_last_name' => $familyNameHead,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createPatientInHousehold(int $householdId, string $firstName, string $lastName): int
    {
        return DB::table('patients')->insertGetId([
            'household_id' => $householdId,
            'first_name' => $firstName,
            'last_name' => $lastName,
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

    private function enrollmentPayload(array $overrides = []): array
    {
        return array_merge([
            'create_new_household' => 0,
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
            'is_philhealth_member' => 'n',
            'is_pcb_member' => 'n',
        ], $overrides);
    }

    /**
     * Enroll a patient into the given (out-of-zone) household and return the new patient id.
     */
    private function enrollIntoHousehold(User $bhw, int $householdId): int
    {
        $response = $this->actingAs($bhw)->post('/patients', $this->enrollmentPayload([
            'household_id' => $householdId,
        ]));

        $response->assertStatus(302);

        $patientId = DB::table('patients')->where('last_name', 'Dela Cruz')->value('id');
        $this->assertNotNull($patientId);

        $this->assertStringContainsString('/patients/'.$patientId, (string) $response->headers->get('Location'));

        return (int) $patientId;
    }

    /** @test */
    public function bhw_household_search_includes_out_of_zone_households(): void
    {
        $bhw = $this->createZoneAssignedBhw(1);
        $this->createZone(2);
        $householdId = $this->createHouseholdInZone(2, 'Agad');

        $this->actingAs($bhw)
            ->getJson('/search/households?query=Agad')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $householdId);
    }

    /** @test */
    public function bhw_can_enroll_patient_into_out_of_zone_household_and_keeps_access(): void
    {
        $bhw = $this->createZoneAssignedBhw(1);
        $this->createZone(2);
        $householdId = $this->createHouseholdInZone(2, 'Agad');

        $patientId = $this->enrollIntoHousehold($bhw, $householdId);

        $this->assertDatabaseHas('patients', [
            'id' => $patientId,
            'household_id' => $householdId,
            'enrolled_by' => $bhw->id,
        ]);

        $this->actingAs($bhw)->get('/patients/'.$patientId)->assertOk();
    }

    /** @test */
    public function bhw_patient_search_includes_self_enrolled_out_of_zone_patient(): void
    {
        $bhw = $this->createZoneAssignedBhw(1);
        $this->createZone(2);
        $householdId = $this->createHouseholdInZone(2, 'Agad');

        $patientId = $this->enrollIntoHousehold($bhw, $householdId);

        $this->actingAs($bhw)
            ->getJson('/search/patients?query=Dela')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $patientId);
    }

    /** @test */
    public function bhw_can_create_consultation_for_self_enrolled_out_of_zone_patient(): void
    {
        $bhw = $this->createZoneAssignedBhw(1);
        $this->createZone(2);
        $householdId = $this->createHouseholdInZone(2, 'Agad');

        $patientId = $this->enrollIntoHousehold($bhw, $householdId);

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

        $response = $this->actingAs($bhw)
            ->postJson("/patients/{$patientId}/consultations", $consultationPayload);

        $response->assertStatus(302);
        $this->assertStringContainsString('/consultations/', (string) $response->headers->get('Location'));
    }

    /** @test */
    public function bhw_still_cannot_view_unrelated_out_of_zone_patient(): void
    {
        $bhw = $this->createZoneAssignedBhw(1);
        $this->createZone(2);
        $householdId = $this->createHouseholdInZone(2, 'Agad');
        $patientId = $this->createPatientInHousehold($householdId, 'Maria', 'Agad');

        $this->actingAs($bhw)->get('/patients/'.$patientId)->assertForbidden();
    }

    /** @test */
    public function bhw_can_enroll_infant_into_out_of_zone_household(): void
    {
        $bhw = $this->createZoneAssignedBhw(1);
        $this->createZone(2);
        $householdId = $this->createHouseholdInZone(2, 'Agad');

        $response = $this->actingAs($bhw)->post('/immunizations/infants', [
            'household_id' => $householdId,
            'first_name' => 'Baby',
            'last_name' => 'Agad',
            'sex' => 'Male',
            'date_of_birth' => now()->subDays(40)->toDateString(),
            'birth_weight' => '3.2',
            'mother_name' => 'Maria Agad',
            'guardian_name' => 'Maria Agad',
        ]);

        $response->assertStatus(302);

        $infantId = (int) DB::table('patients')->where('first_name', 'Baby')->value('id');
        $this->assertGreaterThan(0, $infantId);

        $response->assertRedirect(route('immunizations.patient', $infantId));

        $this->assertDatabaseHas('patients', [
            'id' => $infantId,
            'household_id' => $householdId,
            'enrolled_by' => $bhw->id,
        ]);
    }

    /** @test */
    public function bhw_household_match_includes_out_of_zone_households(): void
    {
        $bhw = $this->createZoneAssignedBhw(1);
        $this->createZone(2);
        $this->createHouseholdInZone(2, 'Agad');

        $this->actingAs($bhw)
            ->getJson('/immunizations/household-match?surname=Agad')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.display_name', 'Agad');
    }

    /** @test */
    public function zone_scoped_worker_can_batch_enroll_into_out_of_zone_household(): void
    {
        $worker = $this->createZoneAssignedBhw(1, ['nurse_batch_entry']);
        $this->createZone(2);
        $householdId = $this->createHouseholdInZone(2, 'Agad');

        $response = $this->actingAs($worker)->post('/batch-entry/patient', [
            'create_new_household' => 0,
            'household_id' => $householdId,
            'first_name' => 'Batch',
            'last_name' => 'Crosszone',
            'middle_name' => '',
            'sex' => 'Female',
            'date_of_birth' => '1992-02-02',
            'civil_status' => 'Single (Walang Asawa)',
            'is_philhealth_member' => 'n',
            'is_pcb_member' => 'n',
        ]);

        $response->assertStatus(302);
        $this->assertStringContainsString('/batch-entry', (string) $response->headers->get('Location'));

        $this->assertDatabaseHas('patients', [
            'household_id' => $householdId,
            'last_name' => 'Crosszone',
            'enrolled_by' => $worker->id,
        ]);
    }
}
