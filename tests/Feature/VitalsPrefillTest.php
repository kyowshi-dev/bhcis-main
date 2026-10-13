<?php

namespace Tests\Feature;

use App\Models\Consultation;
use App\Models\HealthWorker;
use App\Models\Household;
use App\Models\Immunization;
use App\Models\Patient;
use App\Models\Pregnancy;
use App\Models\User;
use App\Models\Vaccine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class VitalsPrefillTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('permissions')->insertOrIgnore([
            ['name' => 'patients', 'description' => 'Access to Patients module', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'consultations', 'description' => 'Access to Consultations module', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'maternal', 'description' => 'Maternal care module', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'immunizations', 'description' => 'Immunizations module', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('zones')->insertOrIgnore([
            'id' => 1, 'zone_number' => 'Zone 1', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_retake_form_prefills_the_latest_vitals_of_the_consultation(): void
    {
        $user = $this->userWithPermissions(['patients', 'consultations']);
        $patient = $this->makePatient();
        $consultation = $this->startConsultation($user, $patient, 'doctor_review');

        $response = $this->actingAs($user)->get(route('consultations.show', $consultation->id));

        $response->assertOk();
        $response->assertSee('name="bp_systolic" id="retake_bp_systolic"', false);
        $response->assertSee('name="bp_diastolic" id="retake_bp_diastolic"', false);
        $response->assertSee('name="temperature" id="retake_temperature"', false);
        $response->assertSee('name="weight" id="retake_weight"', false);
        $response->assertSee('name="height" id="retake_height"', false);
        $response->assertSee('value="120"', false);
        $response->assertSee('value="80"', false);
        $response->assertSee('value="37.5"', false);
        $response->assertSee('value="60"', false);
        $response->assertSee('value="165"', false);
    }

    public function test_retake_form_does_not_prefill_when_the_consultation_has_no_vitals(): void
    {
        $user = $this->userWithPermissions(['patients', 'consultations']);
        $patient = $this->makePatient();
        $consultation = $this->startConsultation($user, $patient, 'doctor_review');

        DB::table('vitals')->where('consultation_id', $consultation->id)->delete();

        $response = $this->actingAs($user)->get(route('consultations.show', $consultation->id));

        $response->assertOk();
        $response->assertSee('name="bp_systolic" id="retake_bp_systolic"', false);
        $response->assertSee('name="weight" id="retake_weight"', false);
        $response->assertDontSee('value="120"', false);
        $response->assertSee('value=""', false);
    }

    public function test_prenatal_visit_form_prefills_previous_vitals_and_measurements(): void
    {
        $user = $this->userWithPermissions(['maternal']);
        $pregnancy = $this->makePregnancy();

        $this->actingAs($user)->post(route('maternal.prenatal.visits.store', $pregnancy->id), [
            'visit_date' => now()->subWeek()->toDateString(),
            'mode_of_transaction' => 'Walk-in',
            'nature_of_visit' => 'New Consultation/Case',
            'bp_systolic' => 118,
            'bp_diastolic' => 76,
            'temperature' => 36.7,
            'weight' => 58.4,
            'height' => 159,
            'fundic_height_cm' => 22.5,
            'fetal_heart_tone_bpm' => 145,
        ])->assertSessionHasNoErrors();

        $response = $this->actingAs($user)->get(route('maternal.prenatal.patient', $pregnancy->patient_id));

        $response->assertOk();
        $response->assertSee('name="bp_systolic" id="pv_bp_systolic"', false);
        $response->assertSee('name="bp_diastolic" id="pv_bp_diastolic"', false);
        $response->assertSee('name="temperature" id="pv_temperature"', false);
        $response->assertSee('name="weight" id="pv_weight"', false);
        $response->assertSee('name="height" id="pv_height"', false);
        $response->assertSee('value="118"', false);
        $response->assertSee('value="76"', false);
        $response->assertSee('value="36.7"', false);
        $response->assertSee('value="58.4"', false);
        $response->assertSee('value="159"', false);
        $response->assertSee('name="fundic_height_cm" value="22.5"', false);
        $response->assertSee('name="fetal_heart_tone_bpm" value="145"', false);
    }

    public function test_immunization_administer_prefills_previous_measurements(): void
    {
        $user = $this->userWithPermissions(['immunizations']);
        $infant = $this->makeInfant();
        $vaccine = Vaccine::where('vaccine_code', 'PENTA')->firstOrFail();

        Immunization::create([
            'patient_id' => $infant->id,
            'vaccine_id' => $vaccine->id,
            'dose_number' => 1,
            'date_given' => now()->subMonth()->toDateString(),
            'temp_recorded' => 36.8,
            'child_weight_kg' => 7.2,
            'child_height_cm' => 68.5,
        ]);

        $response = $this->actingAs($user)->get(route('immunizations.patient', $infant->id));

        $response->assertOk();
        $response->assertSee('value="36.8" placeholder="e.g. 36.5"', false);
        $response->assertSee('value="7.2" placeholder="e.g. 6.5"', false);
        $response->assertSee('value="68.5" placeholder="e.g. 65"', false);
    }

    public function test_immunization_administer_leaves_measurements_empty_for_first_dose(): void
    {
        $user = $this->userWithPermissions(['immunizations']);
        $infant = $this->makeInfant();

        $response = $this->actingAs($user)->get(route('immunizations.patient', $infant->id));

        $response->assertOk();
        $response->assertSee('value="" placeholder="e.g. 36.5"', false);
        $response->assertSee('value="" placeholder="e.g. 6.5"', false);
        $response->assertSee('value="" placeholder="e.g. 65"', false);
    }

    private function userWithPermissions(array $permissions): User
    {
        $user = $this->createUserWithPermissions($permissions);

        HealthWorker::create([
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => 'Worker',
            'role' => 'BHW',
        ]);

        return $user;
    }

    private function makePatient(): Patient
    {
        $household = Household::create(['zone_id' => 1, 'head_last_name' => 'Doe']);

        return Patient::create([
            'household_id' => $household->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'sex' => 'Female',
            'date_of_birth' => '1990-01-01',
            'civil_status' => 'Single',
            'mother_name' => 'Jane Senior',
            'spouse_name' => 'N/A',
            'family_relationship' => 'Mother',
            'residential_address' => 'Sta. Ana, Tagoloan',
        ]);
    }

    private function startConsultation(User $user, Patient $patient, string $status): Consultation
    {
        $this->actingAs($user)->post("/patients/{$patient->id}/consultations", [
            'mode_of_transaction' => 'Walk-in',
            'nature_of_visit' => 'Checkup',
            'purpose_of_visit' => 'General checkup',
            'chief_complaint' => 'Fever',
            'bp_systolic' => 120,
            'bp_diastolic' => 80,
            'temperature' => 37.5,
            'weight' => 60,
            'height' => 165,
        ])->assertRedirect();

        $consultation = Consultation::where('patient_id', $patient->id)->firstOrFail();
        $consultation->update(['status' => $status]);

        return $consultation;
    }

    private function makePregnancy(): Pregnancy
    {
        return Pregnancy::create([
            'patient_id' => $this->makePatient()->id,
            'status' => Pregnancy::STATUS_ACTIVE,
            'gravidity' => 1,
            'parity' => 0,
            'term' => 0,
            'preterm' => 0,
            'livebirth' => 0,
            'abortion' => 0,
            'lmp' => now()->subMonths(5)->toDateString(),
            'edc' => now()->addMonths(4)->toDateString(),
            'syphilis_result' => 'negative',
            'penicillin' => 'no',
        ]);
    }

    private function makeInfant(): Patient
    {
        return Patient::create([
            'household_id' => Household::create(['zone_id' => 1, 'head_last_name' => 'Dela Cruz'])->id,
            'first_name' => 'Baby',
            'last_name' => 'Dela Cruz',
            'sex' => 'Male',
            'date_of_birth' => now()->subDays(70)->toDateString(),
            'civil_status' => 'Single',
            'mother_name' => 'Maria',
            'spouse_name' => '',
            'family_relationship' => 'Son',
            'residential_address' => 'Zone 1 Sta. Ana',
            'is_immunization_enrolled' => true,
        ]);
    }
}
