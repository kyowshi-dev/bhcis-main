<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class PatientEnrollmentWizardTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    private function createZoneAssignedBhw(int $zoneId = 1): User
    {
        $user = $this->createUserWithNamedRole('BHW', [
            'consultations', 'immunizations', 'maternal', 'reports', 'print_handouts', 'dashboard_handouts',
        ]);

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

    private function createHousehold(int $zoneId, string $familyNameHead): int
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

    private function createHouseholdMember(int $householdId, ?int $patientId, string $firstName, string $lastName): int
    {
        return DB::table('household_members')->insertGetId([
            'household_id' => $householdId,
            'patient_id' => $patientId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'sex' => 'Female',
            'date_of_birth' => '1990-01-01',
            'relationship' => 'Daughter',
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

    /** @test */
    public function create_form_preselects_household_and_member_from_query_params(): void
    {
        $bhw = $this->createZoneAssignedBhw();
        $householdId = $this->createHousehold(1, 'Santos');
        $memberId = $this->createHouseholdMember($householdId, null, 'Ana', 'Santos');

        $response = $this->actingAs($bhw)
            ->get('/patients/create?household_id='.$householdId.'&member_id='.$memberId);

        $response->assertOk();
        $response->assertViewHas('selectedHouseholdId', $householdId);
        $response->assertViewHas('selectedMemberId', $memberId);
    }

    /** @test */
    public function store_rejects_household_member_already_linked_to_another_patient(): void
    {
        $bhw = $this->createZoneAssignedBhw();
        $householdId = $this->createHousehold(1, 'Santos');
        $existingPatientId = $this->createPatientInHousehold($householdId, 'Ana', 'Santos');
        $linkedMemberId = $this->createHouseholdMember($householdId, $existingPatientId, 'Ana', 'Santos');

        $response = $this->actingAs($bhw)->post('/patients', $this->enrollmentPayload([
            'household_id' => $householdId,
            'household_member_id' => $linkedMemberId,
        ]));

        $response->assertSessionHasErrors('household_member_id');
        $this->assertDatabaseMissing('patients', [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
        ]);
    }

    /** @test */
    public function store_links_unlinked_household_member_to_new_patient(): void
    {
        $bhw = $this->createZoneAssignedBhw();
        $householdId = $this->createHousehold(1, 'Santos');
        $memberId = $this->createHouseholdMember($householdId, null, 'Ana', 'Santos');

        $response = $this->actingAs($bhw)->post('/patients', $this->enrollmentPayload([
            'household_id' => $householdId,
            'household_member_id' => $memberId,
        ]));

        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();

        $patientId = DB::table('patients')->where('last_name', 'Dela Cruz')->value('id');
        $this->assertNotNull($patientId);

        $this->assertDatabaseHas('household_members', [
            'id' => $memberId,
            'patient_id' => $patientId,
        ]);
    }
}
