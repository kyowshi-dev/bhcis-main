<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Patient;
use App\Models\Vaccine;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

/**
 * Reproduces the reported bug: after enrollment, consultation submission,
 * and vaccine dose administration, the landing page must actually render
 * the flashed success message - not just set it in the session.
 */
class SuccessFlashDisplayTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('permissions')->insertOrIgnore([
            ['name' => 'patients', 'description' => 'Access to Patients module', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'consultations', 'description' => 'Access to Consultations module', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'immunizations', 'description' => 'Immunizations module', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_patient_enrollment_renders_success_message_on_profile(): void
    {
        $this->actingAs($this->createUserWithPermissions(['patients']));
        $this->createZone();

        $response = $this->post(route('patients.store'), $this->enrollmentPayload());

        $patient = Patient::query()->latest('id')->firstOrFail();

        $response->assertRedirect(route('patients.show', $patient->id));
        $response->assertSessionHas('success', 'Patient registered successfully!');

        $this->get(route('patients.show', $patient->id))
            ->assertOk()
            ->assertSee('Patient registered successfully!');
    }

    public function test_consultation_submission_renders_success_message(): void
    {
        [$worker, $patientId] = $this->createClinicalFixture();

        $response = $this->actingAs($worker)
            ->post("/patients/{$patientId}/consultations", $this->consultationPayload());

        $consultationId = (int) DB::table('consultations')->where('patient_id', $patientId)->value('id');

        $this->assertNotNull($consultationId);
        $response->assertRedirect(route('consultations.show', $consultationId));
        $response->assertSessionHas('success');

        $this->get(route('consultations.show', $consultationId))
            ->assertOk()
            ->assertSee('Consultation started');
    }

    public function test_vaccine_dose_administration_renders_success_message(): void
    {
        $user = $this->createUserWithPermissions(['immunizations']);
        $infant = $this->makeInfant(now()->subDays(100));
        $vaccine = $this->vaccine('PENTA');

        $response = $this->actingAs($user)
            ->from(route('immunizations.patient', $infant->id))
            ->post(route('immunizations.administer', $infant->id), [
                'vaccine_id' => $vaccine->id,
                'date_given' => now()->toDateString(),
                'child_weight_kg' => '6.5',
                'child_height_cm' => '65',
            ]);

        $response->assertRedirect(route('immunizations.patient', $infant->id));
        $response->assertSessionHas('success');

        $this->get(route('immunizations.patient', $infant->id))
            ->assertOk()
            ->assertSee('recorded (dose');
    }

    private function createZone(): void
    {
        DB::table('zones')->insertOrIgnore([
            'id' => 1,
            'zone_number' => 'Zone 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function enrollmentPayload(array $overrides = []): array
    {
        return array_merge([
            'create_new_household' => 1,
            'new_household_zone_id' => 1,
            'new_household_family_name_head' => 'Dela Cruz',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'middle_name' => 'Santos',
            'sex' => 'Male',
            'date_of_birth' => '1990-01-01',
            'birth_place' => 'Sta. Ana, Tagoloan',
            'civil_status' => 'Single (Walang Asawa)',
            'mother_name' => 'Maria Dela Cruz',
            'spouse_name' => 'Rosa Dela Cruz',
            'family_relationship' => 'Son',
            'is_philhealth_member' => 'n',
            'is_pcb_member' => 'n',
        ], $overrides);
    }

    private function consultationPayload(): array
    {
        return [
            'mode_of_transaction' => 'Walk-in',
            'nature_of_visit' => 'Checkup',
            'purpose_of_visit' => 'General checkup',
            'chief_complaint' => 'Fever',
            'bp_systolic' => 120,
            'bp_diastolic' => 80,
            'temperature' => 37.5,
            'weight' => 60,
            'height' => 165,
        ];
    }

    private function createClinicalFixture(): array
    {
        $user = $this->createUserWithPermissions(['patients', 'consultations']);

        DB::table('health_workers')->insert([
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => 'Worker',
            'role' => 'Midwife',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('zones')->insertOrIgnore([
            'id' => 1,
            'zone_number' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $householdId = DB::table('households')->insertGetId([
            'zone_id' => 1,
            'family_name_head' => 'Test',
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

    private function makeInfant(Carbon $dob): Patient
    {
        DB::table('zones')->insertOrIgnore([
            'id' => 1,
            'zone_number' => 'Zone 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $household = Household::create([
            'zone_id' => 1,
            'family_name_head' => 'Dela Cruz',
        ]);

        return Patient::create([
            'household_id' => $household->id,
            'first_name' => 'Baby',
            'last_name' => 'Dela Cruz',
            'sex' => 'Male',
            'date_of_birth' => $dob->toDateString(),
            'civil_status' => 'Single',
            'mother_name' => 'Maria',
            'spouse_name' => '',
            'family_relationship' => 'Son',
            'residential_address' => 'Zone 1 Sta. Ana',
            'is_immunization_enrolled' => true,
        ]);
    }

    private function vaccine(string $code): Vaccine
    {
        return Vaccine::where('vaccine_code', $code)->firstOrFail();
    }
}
