<?php

namespace Tests\Feature;

use App\Models\FamilyPlanningClient;
use App\Models\Household;
use App\Models\Patient;
use App\Models\PostnatalRecord;
use App\Models\Pregnancy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class PrintFormSmokeTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['consultations', 'print_handouts', 'maternal'] as $permission) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $permission,
                'description' => 'Print form smoke test: '.$permission,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('zones')->insertOrIgnore([
            'id' => 1,
            'zone_number' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function user(): User
    {
        return $this->createUserWithPermissions(['consultations', 'print_handouts', 'maternal']);
    }

    protected function patient(array $overrides = []): Patient
    {
        return Patient::create(array_merge([
            'household_id' => Household::create(['zone_id' => 1, 'family_name_head' => 'Dela Cruz'])->id,
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'middle_name' => 'Reyes',
            'sex' => 'Female',
            'date_of_birth' => '1995-04-12',
            'civil_status' => 'Married',
            'employment_status' => 'Employed',
            'mother_name' => 'Rosa Reyes',
            'spouse_name' => 'Juan Dela Cruz',
            'family_relationship' => 'Mother',
            'residential_address' => 'Zone 1, Sta. Ana',
            'birth_place' => 'Sta. Ana',
            'educational_attainment' => 'College',
            'is_philhealth_member' => 'y',
            'status_type' => 'Member',
            'philhealth_no' => '12-345678901-1',
        ], $overrides));
    }

    protected function pregnancy(Patient $patient): Pregnancy
    {
        return Pregnancy::create([
            'patient_id' => $patient->id,
            'status' => Pregnancy::STATUS_ACTIVE,
            'gravidity' => 2,
            'parity' => 1,
            'term' => 1,
            'preterm' => 0,
            'livebirth' => 1,
            'abortion' => 0,
            'lmp' => '2026-01-10',
            'edc' => '2026-10-17',
            'syphilis_result' => 'negative',
            'penicillin' => 'yes',
        ]);
    }

    protected function postnatal(Patient $patient, Pregnancy $pregnancy): PostnatalRecord
    {
        return PostnatalRecord::create([
            'patient_id' => $patient->id,
            'pregnancy_id' => $pregnancy->id,
            'pregnancy_outcome' => 'live_birth',
            'prenatal_visits_completed' => 4,
            'place_delivered' => 'health_center',
            'mode_of_delivery' => 'normal_vaginal',
            'attendant_at_birth' => 'midwife',
            'delivery_date' => now()->subDays(30)->toDateString(),
            'delivery_time' => '08:30:00',
            'breastfeeding_date' => now()->subDays(30)->toDateString(),
            'breastfeeding_time' => '09:00:00',
            'child_first_name' => 'Baby',
            'child_last_name' => 'Santos',
            'child_sex' => 'F',
            'child_birth_weight_kg' => 3.1,
            'child_birth_length_cm' => 49,
            'recorded_by' => null,
        ]);
    }

    protected function fpClient(Patient $patient): FamilyPlanningClient
    {
        return FamilyPlanningClient::create([
            'patient_id' => $patient->id,
            'type_of_client' => FamilyPlanningClient::TYPE_NEW_ACCEPTOR,
            'method' => 'Pills',
            'is_active' => true,
        ]);
    }

    protected function consultation(Patient $patient): int
    {
        $workerUserId = $this->user()->id;
        DB::table('health_workers')->insert([
            'user_id' => $workerUserId,
            'first_name' => 'Teresa',
            'last_name' => 'Cruz',
            'role' => 'BHW',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $workerId = (int) DB::table('health_workers')->where('user_id', $workerUserId)->value('id');

        return (int) DB::table('consultations')->insertGetId([
            'patient_id' => $patient->id,
            'worker_id' => $workerId,
            'attending_doctor_id' => $workerId,
            'status' => 'completed',
            'nature_of_visit' => 'New Consultation/Case',
            'mode_of_transaction' => 'Walk-in',
            'complaint_text' => 'Fever and cough for 3 days',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_handout_renders_bhcis_branding_without_iclinic_header(): void
    {
        $patient = $this->patient();
        $pregnancy = $this->pregnancy($patient);
        $this->postnatal($patient, $pregnancy);
        $this->fpClient($patient);
        $consultationId = $this->consultation($patient);

        $response = $this->actingAs($this->user())->get(route('consultations.handout', $consultationId));

        $response->assertOk()
            ->assertSee('Generated by BHCIS - not an iClinicSys form', false)
            ->assertSee('Barangay Health Center Information System', false)
            ->assertSee('Patient Enrolment Record', false)
            ->assertSee('Individual Treatment Record', false)
            ->assertSee('I. Personal Information', false)
            ->assertSee("VII. Patient's Consent", false)
            ->assertDontSee('System Disclaimer', false)
            ->assertSee('Family Planning', false)
            ->assertSee('Child Immunization', false)
            ->assertSee('Postpartum', false)
            ->assertSee('Barangay Health Center Information System (BHCIS)', false)
            ->assertDontSee('Integrated Clinic Information System', false)
            ->assertDontSee('iCLINICSYS', false)
            ->assertDontSee('CHU/RHU', false);
    }

    public function test_handout_refuses_completed_status_requirement(): void
    {
        $patient = $this->patient();
        $consultationId = $this->consultation($patient);
        DB::table('consultations')->where('id', $consultationId)->update(['status' => 'in_progress']);

        $this->actingAs($this->user())
            ->get(route('consultations.handout', $consultationId))
            ->assertForbidden();
    }

    public function test_prenatal_print_renders_bhcis_header(): void
    {
        $patient = $this->patient();
        $pregnancy = $this->pregnancy($patient);

        $this->actingAs($this->user())
            ->get(route('maternal.pregnancies.print', $pregnancy->id))
            ->assertOk()
            ->assertSee('Generated by BHCIS - not an iClinicSys form', false)
            ->assertSee('Prenatal Record', false)
            ->assertSee('BHCIS System Sta. Ana', false)
            ->assertDontSee('iCLINICSYS', false);
    }

    public function test_family_planning_print_renders_bhcis_header(): void
    {
        $patient = $this->patient();
        $client = $this->fpClient($patient);

        $this->actingAs($this->user())
            ->get(route('maternal.family-planning.print', $client->id))
            ->assertOk()
            ->assertSee('Generated by BHCIS - not an iClinicSys form', false)
            ->assertSee('Family Planning Client Record', false)
            ->assertDontSee('iCLINICSYS', false);
    }

    public function test_postnatal_print_renders_bhcis_header(): void
    {
        $patient = $this->patient();
        $pregnancy = $this->pregnancy($patient);
        $record = $this->postnatal($patient, $pregnancy);

        $this->actingAs($this->user())
            ->get(route('maternal.postnatal.print', $record->id))
            ->assertOk()
            ->assertSee('Generated by BHCIS - not an iClinicSys form', false)
            ->assertSee('Postpartum Care Record', false)
            ->assertDontSee('iCLINICSYS', false);
    }
}
