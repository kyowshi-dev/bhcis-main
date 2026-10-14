<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class SearchDiagnosesTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('permissions')->insert([
            ['name' => 'patients', 'description' => 'Access to Patients module', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'consultations', 'description' => 'Access to Consultations module', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function userWithConsultations(): User
    {
        return $this->createUserWithPermissions(['patients', 'consultations']);
    }

    public function test_diagnoses_are_searched_from_the_local_lookup(): void
    {
        $localId = DB::table('diagnosis_lookup')->insertGetId([
            'diagnosis_code' => 'J06.9',
            'diagnosis_name' => 'Acute upper respiratory infection',
        ]);

        $response = $this->actingAs($this->userWithConsultations())
            ->getJson('/search/diagnoses?query=upper');

        $response->assertOk()
            ->assertJson([['id' => $localId, 'text' => 'J06.9 - Acute upper respiratory infection']]);
    }

    public function test_a_local_diagnosis_can_be_saved(): void
    {
        DB::table('diagnosis_lookup')->insert([
            'diagnosis_code' => 'E11',
            'diagnosis_name' => 'Type 2 diabetes mellitus',
        ]);

        [$bhw, $patientId] = $this->createClinicalFixture('BHW');
        $nurse = $this->createWorkerUser('Nurse');
        $doctor = $this->createWorkerUser('Doctor');

        $this->actingAs($bhw)->post("/patients/{$patientId}/consultations", [
            'mode_of_transaction' => 'Walk-in',
            'nature_of_visit' => 'Checkup',
            'purpose_of_visit' => 'General checkup',
            'chief_complaint' => 'Fatigue',
            'bp_systolic' => 120,
            'bp_diastolic' => 80,
            'temperature' => 36.8,
            'weight' => 65,
            'height' => 170,
        ]);

        $consultationId = (int) DB::table('consultations')->where('patient_id', $patientId)->value('id');

        $this->actingAs($nurse)->post("/consultations/{$consultationId}/acknowledge-intake");

        $search = $this->actingAs($doctor)->getJson('/search/diagnoses?query=diabetes');
        $search->assertOk();
        $diagnosisId = $search->json()[0]['id'];
        $this->assertIsInt($diagnosisId);

        $this->actingAs($doctor)->post("/consultations/{$consultationId}/diagnosis", [
            'diagnosis_id' => $diagnosisId,
        ])->assertRedirect();

        $this->assertSame(
            $diagnosisId,
            (int) DB::table('diagnosis_records')->where('consultation_id', $consultationId)->value('diagnosis_id')
        );
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createClinicalFixture(string $role): array
    {
        $user = $this->createWorkerUser($role);

        DB::table('zones')->insert(['id' => 1, 'zone_number' => '1']);
        $householdId = DB::table('households')->insertGetId([
            'zone_id' => 1,
            'head_last_name' => 'Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $patientId = DB::table('patients')->insertGetId([
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

        return [$user, $patientId];
    }

    private function createWorkerUser(string $role): User
    {
        $user = $this->createUserWithPermissions(['patients', 'consultations']);

        DB::table('health_workers')->insert([
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => $role,
            'role' => $role,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }
}
