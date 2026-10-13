<?php

namespace Tests\Feature;

use App\Models\ApplicationSetting;
use App\Models\Death;
use App\Models\FamilyPlanningClient;
use App\Models\Household;
use App\Models\Patient;
use App\Models\User;
use App\Services\MchEpiFpReportService;
use App\Services\MorbidityReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class FhisReportsTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('permissions')->insertOrIgnore([
            'name' => 'reports',
            'description' => 'Access to reports module',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('zones')->insertOrIgnore([
            'id' => 1,
            'zone_number' => 'Zone 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function authorizedUser(): User
    {
        return $this->createUserWithPermissions(['reports']);
    }

    private function healthWorkerFor(User $user, string $lastName = 'Worker'): int
    {
        return DB::table('health_workers')->insertGetId([
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => $lastName,
            'role' => 'Midwife',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function patient(string $lastName = 'Reyes', int $zoneId = 1): Patient
    {
        return Patient::create([
            'household_id' => Household::create(['zone_id' => $zoneId, 'head_last_name' => $lastName])->id,
            'first_name' => 'Liza',
            'last_name' => $lastName,
            'sex' => 'Female',
            'date_of_birth' => '1995-08-15',
            'civil_status' => 'Married',
            'mother_name' => '',
            'spouse_name' => 'Marco Reyes',
            'family_relationship' => 'Mother',
            'residential_address' => 'Zone 1 Sta. Ana',
        ]);
    }

    private function filters(?string $from = null, ?string $to = null): array
    {
        return [
            'from' => $from ?? now()->startOfMonth()->toDateString(),
            'to' => $to ?? now()->endOfMonth()->toDateString(),
        ];
    }

    private function pregnancy(Patient $patient, int $workerId, string $createdAt = 'now'): int
    {
        $timestamp = $createdAt === 'now' ? now() : $createdAt;

        return DB::table('pregnancies')->insertGetId([
            'patient_id' => $patient->id,
            'status' => 'active',
            'gravidity' => 1,
            'parity' => 0,
            'lmp' => now()->subMonths(4)->toDateString(),
            'syphilis_result' => 'negative',
            'iron_taken' => 1,
            'tt_date' => now()->toDateString(),
            'recorded_by' => $workerId,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    private function vaccineId(string $code = 'BCG_TEST'): int
    {
        return DB::table('vaccines_lookup')->insertGetId([
            'vaccine_code' => $code,
            'vaccine_name' => 'BCG Test',
            'category' => 'Child',
            'sort_order' => 99,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_reports_index_requires_reports_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('reports.index'))
            ->assertForbidden();
    }

    public function test_reports_index_uses_fhis_style_claims(): void
    {
        $user = $this->authorizedUser();

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('FHSIS-style reports formatted to support RHU submission')
            ->assertDontSee('Official report formats')
            ->assertSee('FHSIS-style leading causes of morbidity (ICD code, diagnosis name, case count)')
            ->assertDontSee('FHSIS standard format');
    }

    public function test_morbidity_report_screen_disclaimer_footer(): void
    {
        $user = $this->authorizedUser();

        $this->actingAs($user)
            ->get(route('reports.morbidity', ['month' => now()->month, 'year' => now()->year]))
            ->assertOk()
            ->assertSee('Formatted to match DOH FHSIS report layouts')
            ->assertDontSee('In Compliance with Department of Health');
    }

    public function test_mch_epi_fp_report_screen_disclaimer_footer(): void
    {
        $user = $this->authorizedUser();

        $this->actingAs($user)
            ->get(route('reports.mch-epi-fp', $this->filters()))
            ->assertOk()
            ->assertSee('Formatted to match DOH FHSIS report layouts')
            ->assertDontSee('In Compliance with Department of Health');
    }

    public function test_morbidity_pdf_template_includes_non_official_disclaimer(): void
    {
        $html = view('pdfs.morbidity_report', [
            'rows' => collect(),
            'totalCases' => 0,
            'reportDate' => now()->startOfMonth()->format('m/d/Y').' - '.now()->endOfMonth()->format('m/d/Y'),
            'month' => now()->month,
            'year' => now()->year,
            'sexLabel' => 'All Sex',
            'zoneLabel' => 'All Zones',
            'ageGroupLabel' => 'All ages',
        ])->render();

        $this->assertStringContainsString('Generated by BHCIS - supports, does not replace, official FHSIS forms', $html);
    }

    public function test_mch_epfp_pdf_template_includes_non_official_disclaimer(): void
    {
        $html = view('pdfs.mch_epi_fp_report', [
            'summaries' => [
                'maternal' => null,
                'epi' => null,
                'fp' => null,
            ],
            'rows' => collect(),
            'totalRows' => 0,
            'reportDate' => now()->startOfMonth()->format('m/d/Y').' - '.now()->endOfMonth()->format('m/d/Y'),
            'zoneLabel' => 'All Zones',
            'programLabel' => 'All Programs',
        ])->render();

        $this->assertStringContainsString('Generated by BHCIS - supports, does not replace, official FHSIS forms', $html);
    }

    public function test_mch_epi_fp_report_renders_register_with_patient_worker_and_date(): void
    {
        $user = $this->authorizedUser();
        $workerId = $this->healthWorkerFor($user);
        $patient = $this->patient();

        $pregnancyId = $this->pregnancy($patient, $workerId);

        DB::table('prenatal_visits')->insert([
            'pregnancy_id' => $pregnancyId,
            'visit_date' => now()->toDateString(),
            'recorded_by' => $workerId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('immunization_records')->insert([
            'patient_id' => $patient->id,
            'vaccine_id' => $this->vaccineId(),
            'dose_number' => 1,
            'date_given' => now()->toDateString(),
            'administered_by' => $workerId,
            'no_show' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $client = FamilyPlanningClient::create([
            'patient_id' => $patient->id,
            'type_of_client' => FamilyPlanningClient::TYPE_NEW_ACCEPTOR,
            'method' => 'Injectable',
            'is_active' => true,
            'recorded_by' => $workerId,
        ]);

        DB::table('family_planning_visits')->insert([
            'client_id' => $client->id,
            'visit_date' => now()->toDateString(),
            'method' => 'Injectable',
            'recorded_by' => $workerId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('reports.mch-epi-fp', $this->filters()))
            ->assertOk()
            ->assertSee('Maternal, EPI & Family Planning Report')
            ->assertSee(now()->startOfMonth()->format('m/d/Y').' - '.now()->endOfMonth()->format('m/d/Y'))
            ->assertSee(now()->format('m/d/Y'))
            ->assertSee('Reyes')
            ->assertSee('Test Worker')
            ->assertSee('Prenatal registration')
            ->assertSee('Prenatal visit')
            ->assertSee('BCG Test 1')
            ->assertSee('New Acceptor - Injectable')
            ->assertSee('Visit - Injectable');
    }

    public function test_mch_epi_fp_report_summaries_count_all_programs(): void
    {
        $user = $this->authorizedUser();
        $workerId = $this->healthWorkerFor($user);
        $patient = $this->patient();

        $pregnancyId = $this->pregnancy($patient, $workerId);

        DB::table('prenatal_visits')->insert([
            'pregnancy_id' => $pregnancyId,
            'visit_date' => now()->toDateString(),
            'recorded_by' => $workerId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('postnatal_records')->insert([
            'patient_id' => $patient->id,
            'pregnancy_id' => $pregnancyId,
            'pregnancy_outcome' => 'live_birth',
            'place_delivered' => 'health_center',
            'mode_of_delivery' => 'normal_vaginal',
            'attendant_at_birth' => 'midwife',
            'delivery_date' => now()->toDateString(),
            'delivery_time' => '09:30:00',
            'breastfeeding_date' => now()->toDateString(),
            'breastfeeding_time' => '10:00:00',
            'postpartum_24h_date' => now()->toDateString(),
            'recorded_by' => $workerId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('immunization_records')->insert([
            'patient_id' => $patient->id,
            'vaccine_id' => $this->vaccineId(),
            'dose_number' => 1,
            'date_given' => now()->toDateString(),
            'administered_by' => $workerId,
            'no_show' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        FamilyPlanningClient::create([
            'patient_id' => $patient->id,
            'type_of_client' => FamilyPlanningClient::TYPE_OTHERS,
            'method' => 'Pills',
            'is_active' => true,
            'recorded_by' => $workerId,
        ]);

        $report = MchEpiFpReportService::query($this->filters(), $user);

        $this->assertSame(1, $report['summaries']['maternal']['newPrenatalClients']);
        $this->assertSame(1, $report['summaries']['maternal']['prenatalVisits']);
        $this->assertSame(1, $report['summaries']['maternal']['totalDeliveries']);
        $this->assertSame(1, $report['summaries']['maternal']['postpartum24h']);
        $this->assertSame(1, $report['summaries']['maternal']['ttDoses']);
        $this->assertSame(1, $report['summaries']['maternal']['ironSupplemented']);
        $this->assertSame(0, $report['summaries']['maternal']['syphilisPositive']);
        $this->assertSame(1, $report['summaries']['epi']['totalDoses']);
        $this->assertSame(1, $report['summaries']['epi']['childDoses']);
        $this->assertSame(0, $report['summaries']['epi']['adultDoses']);
        $this->assertSame(1, $report['summaries']['fp']['totalOthers']);
        $this->assertSame(0, $report['summaries']['fp']['totalNew']);
    }

    public function test_mch_epi_fp_report_shows_maternal_screening_and_attendant_indicators(): void
    {
        $user = $this->authorizedUser();
        $workerId = $this->healthWorkerFor($user);
        $patient = $this->patient();

        $pregnancyId = DB::table('pregnancies')->insertGetId([
            'patient_id' => $patient->id,
            'status' => 'active',
            'gravidity' => 1,
            'parity' => 0,
            'lmp' => now()->subMonths(4)->toDateString(),
            'syphilis_result' => 'positive',
            'iron_taken' => 1,
            'tt_date' => now()->toDateString(),
            'recorded_by' => $workerId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('postnatal_records')->insert([
            'patient_id' => $patient->id,
            'pregnancy_id' => $pregnancyId,
            'pregnancy_outcome' => 'live_birth',
            'place_delivered' => 'health_center',
            'mode_of_delivery' => 'normal_vaginal',
            'attendant_at_birth' => 'midwife',
            'delivery_date' => now()->toDateString(),
            'delivery_time' => '09:30:00',
            'breastfeeding_date' => now()->toDateString(),
            'breastfeeding_time' => '10:00:00',
            'recorded_by' => $workerId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('reports.mch-epi-fp', $this->filters()))
            ->assertOk()
            ->assertSee('TT doses')
            ->assertSee('Iron supplementation')
            ->assertSee('Syphilis screening - reactive')
            ->assertSee('Deliveries by attendant')
            ->assertSee('Midwife');
    }

    public function test_mch_epi_fp_report_respects_date_range(): void
    {
        $user = $this->authorizedUser();
        $workerId = $this->healthWorkerFor($user);
        $patient = $this->patient();

        $pregnancyId = $this->pregnancy($patient, $workerId, now()->subMonth());

        DB::table('prenatal_visits')->insert([
            'pregnancy_id' => $pregnancyId,
            'visit_date' => now()->subMonth()->toDateString(),
            'recorded_by' => $workerId,
            'created_at' => now()->subMonth(),
            'updated_at' => now()->subMonth(),
        ]);

        $currentMonth = MchEpiFpReportService::query($this->filters(), $user);
        $this->assertSame(0, $currentMonth['totalRows']);

        $wideRange = MchEpiFpReportService::query($this->filters(now()->subMonths(2)->startOfMonth()->toDateString()), $user);
        $this->assertSame(2, $wideRange['totalRows']);
        $this->assertSame(1, $wideRange['summaries']['maternal']['prenatalVisits']);
    }

    public function test_mch_epi_fp_program_filter_limits_register_and_summaries(): void
    {
        $user = $this->authorizedUser();
        $workerId = $this->healthWorkerFor($user);
        $patient = $this->patient();

        $this->pregnancy($patient, $workerId);

        DB::table('immunization_records')->insert([
            'patient_id' => $patient->id,
            'vaccine_id' => $this->vaccineId(),
            'dose_number' => 1,
            'date_given' => now()->toDateString(),
            'administered_by' => $workerId,
            'no_show' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $epiOnly = MchEpiFpReportService::query($this->filters() + ['program' => 'epi'], $user);

        $this->assertSame(1, $epiOnly['totalRows']);
        $this->assertSame('EPI Immunization', $epiOnly['rows']->first()->program_label);
        $this->assertNull($epiOnly['summaries']['maternal']);
        $this->assertNull($epiOnly['summaries']['fp']);
        $this->assertNotNull($epiOnly['summaries']['epi']);
        $this->assertSame(1, $epiOnly['programCounts']['epi']);
        $this->assertSame(0, $epiOnly['programCounts']['maternal']);
        $this->assertSame(0, $epiOnly['programCounts']['fp']);
    }

    public function test_mch_epi_fp_report_zone_scoping_hides_other_zones(): void
    {
        $user = $this->createUserWithPermissions(['reports', 'household']);
        $workerId = $this->healthWorkerFor($user);

        DB::table('zones')->insertOrIgnore([
            'id' => 2,
            'zone_number' => 'Zone 2',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('zones')->where('id', 1)->update(['assigned_worker_id' => $workerId]);

        $ownPatient = $this->patient('OwnZone', 1);
        $otherPatient = $this->patient('OtherZone', 2);
        $vaccineId = $this->vaccineId('BCG_SCOPED');

        DB::table('immunization_records')->insert([
            'patient_id' => $otherPatient->id,
            'vaccine_id' => $vaccineId,
            'dose_number' => 1,
            'date_given' => now()->toDateString(),
            'administered_by' => $workerId,
            'no_show' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('immunization_records')->insert([
            'patient_id' => $ownPatient->id,
            'vaccine_id' => $vaccineId,
            'dose_number' => 1,
            'date_given' => now()->toDateString(),
            'administered_by' => $workerId,
            'no_show' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $report = MchEpiFpReportService::query($this->filters(), $user);

        $this->assertSame(1, $report['totalRows']);
        $this->assertSame(1, $report['summaries']['epi']['totalDoses']);
        $this->assertStringContainsString('OwnZone', $report['rows']->first()->patient_name);
    }

    public function test_mch_epi_fp_search_filters_register_by_patient_name(): void
    {
        $user = $this->authorizedUser();
        $workerId = $this->healthWorkerFor($user);
        $delosSantos = $this->patient('Delos Santos');
        $reyes = $this->patient('Reyes');

        $this->pregnancy($delosSantos, $workerId);
        $this->pregnancy($reyes, $workerId);

        $search = MchEpiFpReportService::query($this->filters() + ['search' => 'rey'], $user);

        $this->assertSame(1, $search['totalRows']);
        $this->assertStringContainsString('Reyes', $search['rows']->first()->patient_name);

        $noMatch = MchEpiFpReportService::query($this->filters() + ['search' => 'zzz'], $user);

        $this->assertSame(0, $noMatch['totalRows']);
    }

    public function test_legacy_report_routes_redirect_to_merged_report(): void
    {
        $user = $this->authorizedUser();

        $this->actingAs($user)
            ->get('/reports/maternal-care?month='.now()->month.'&year='.now()->year)
            ->assertRedirect(route('reports.mch-epi-fp', $this->filters()));

        $this->actingAs($user)
            ->get('/reports/immunization')
            ->assertRedirect(route('reports.mch-epi-fp'));

        $this->actingAs($user)
            ->get('/reports/family-planning/download')
            ->assertRedirect(route('reports.mch-epi-fp.download'));

        $this->actingAs($user)
            ->get('/reports/ncd')
            ->assertNotFound();

        $this->actingAs($user)
            ->get('/reports/referrals')
            ->assertNotFound();
    }

    // PR2: Settings projected_population
    public function test_application_setting_projected_population_can_be_read_and_set(): void
    {
        $this->assertSame('0', ApplicationSetting::get('projected_population'));

        ApplicationSetting::set('projected_population', '50000');
        $this->assertSame('50000', ApplicationSetting::get('projected_population'));
    }

    public function test_settings_page_includes_projected_population_field_for_admin(): void
    {
        $admin = $this->createUserWithPermissions(['users', 'reports']);
        $this->actingAs($admin)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Projected Population');
    }

    // PR2: FIC/CIC/CPAB in EPI summaries
    public function test_epi_summaries_include_fic_cpab_counts(): void
    {
        $user = $this->authorizedUser();
        $workerId = $this->healthWorkerFor($user);

        // Assign zone 1 to worker so they can see patients in zone 1
        DB::table('zones')->where('id', 1)->update(['assigned_worker_id' => $workerId]);

        // Create a fully immunized child (BCG, HepB birth, Penta 3, OPV 3, IPV, PCV 3, MCV)
        $ficPatient = $this->patient('FIC', 1);
        $ficPatient->update(['date_of_birth' => now()->subYear()->toDateString(), 'is_immunization_enrolled' => true]);
        $vaccineIds = DB::table('vaccines_lookup')->pluck('id', 'vaccine_code')->toArray();

        $currentDate = now()->toDateString();

        // BCG
        DB::table('immunization_records')->insert([
            'patient_id' => $ficPatient->id, 'vaccine_id' => $vaccineIds['BCG'], 'dose_number' => 1,
            'date_given' => $currentDate, 'administered_by' => $workerId, 'no_show' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        // HepB birth dose (CPAB) - given in current period
        DB::table('immunization_records')->insert([
            'patient_id' => $ficPatient->id, 'vaccine_id' => $vaccineIds['HEPA_B_24H'], 'dose_number' => 1,
            'date_given' => $currentDate, 'administered_by' => $workerId, 'no_show' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        // PENTA 1,2,3
        foreach ([1, 2, 3] as $dose) {
            DB::table('immunization_records')->insert([
                'patient_id' => $ficPatient->id, 'vaccine_id' => $vaccineIds['PENTA'], 'dose_number' => $dose,
                'date_given' => now()->subMonths(4 - $dose)->toDateString(), 'administered_by' => $workerId, 'no_show' => false,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        // OPV 1,2,3
        foreach ([1, 2, 3] as $dose) {
            DB::table('immunization_records')->insert([
                'patient_id' => $ficPatient->id, 'vaccine_id' => $vaccineIds['OPV'], 'dose_number' => $dose,
                'date_given' => now()->subMonths(4 - $dose)->toDateString(), 'administered_by' => $workerId, 'no_show' => false,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        // IPV
        DB::table('immunization_records')->insert([
            'patient_id' => $ficPatient->id, 'vaccine_id' => $vaccineIds['IPV'], 'dose_number' => 1,
            'date_given' => now()->subMonths(2)->toDateString(), 'administered_by' => $workerId, 'no_show' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        // PCV 1,2,3
        foreach ([1, 2, 3] as $dose) {
            DB::table('immunization_records')->insert([
                'patient_id' => $ficPatient->id, 'vaccine_id' => $vaccineIds['PCV'], 'dose_number' => $dose,
                'date_given' => now()->subMonths(4 - $dose)->toDateString(), 'administered_by' => $workerId, 'no_show' => false,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        // MCV (AMV)
        DB::table('immunization_records')->insert([
            'patient_id' => $ficPatient->id, 'vaccine_id' => $vaccineIds['MCV_AMV'], 'dose_number' => 1,
            'date_given' => now()->subMonths(3)->toDateString(), 'administered_by' => $workerId, 'no_show' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Partially immunized child (missing some doses)
        $partialPatient = $this->patient('Partial', 1);
        $partialPatient->update(['date_of_birth' => now()->subYear()->toDateString(), 'is_immunization_enrolled' => true]);
        DB::table('immunization_records')->insert([
            'patient_id' => $partialPatient->id, 'vaccine_id' => $vaccineIds['BCG'], 'dose_number' => 1,
            'date_given' => $currentDate, 'administered_by' => $workerId, 'no_show' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('immunization_records')->insert([
            'patient_id' => $partialPatient->id, 'vaccine_id' => $vaccineIds['HEPA_B_24H'], 'dose_number' => 1,
            'date_given' => $currentDate, 'administered_by' => $workerId, 'no_show' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        // Only PENTA 1
        DB::table('immunization_records')->insert([
            'patient_id' => $partialPatient->id, 'vaccine_id' => $vaccineIds['PENTA'], 'dose_number' => 1,
            'date_given' => now()->subMonths(3)->toDateString(), 'administered_by' => $workerId, 'no_show' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $report = MchEpiFpReportService::query($this->filters(), $user);

        $this->assertArrayHasKey('fic', $report['summaries']['epi']);
        $this->assertArrayHasKey('cpab', $report['summaries']['epi']);
        $this->assertSame(1, $report['summaries']['epi']['fic']);
        $this->assertSame(2, $report['summaries']['epi']['cpab']); // both children got HepB birth dose
    }

    // PR2: Sex disaggregation in EPI and FP
    public function test_epi_summaries_sex_disaggregation(): void
    {
        $user = $this->authorizedUser();
        $workerId = $this->healthWorkerFor($user);

        $male = $this->patient('Male', 1);
        $male->update(['sex' => 'Male', 'date_of_birth' => now()->subYear()->toDateString()]);
        $female = $this->patient('Female', 1);
        $female->update(['sex' => 'Female', 'date_of_birth' => now()->subYear()->toDateString()]);

        $vaccineIds = DB::table('vaccines_lookup')->pluck('id', 'vaccine_code')->toArray();

        // Male gets BCG
        DB::table('immunization_records')->insert([
            'patient_id' => $male->id, 'vaccine_id' => $vaccineIds['BCG'], 'dose_number' => 1,
            'date_given' => now()->toDateString(), 'administered_by' => $workerId, 'no_show' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        // Female gets BCG + HepB
        DB::table('immunization_records')->insert([
            'patient_id' => $female->id, 'vaccine_id' => $vaccineIds['BCG'], 'dose_number' => 1,
            'date_given' => now()->toDateString(), 'administered_by' => $workerId, 'no_show' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('immunization_records')->insert([
            'patient_id' => $female->id, 'vaccine_id' => $vaccineIds['HEPA_B_24H'], 'dose_number' => 1,
            'date_given' => now()->toDateString(), 'administered_by' => $workerId, 'no_show' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $report = MchEpiFpReportService::query($this->filters(), $user);

        $this->assertArrayHasKey('dosesBySex', $report['summaries']['epi']);
        $this->assertSame(1, $report['summaries']['epi']['dosesBySex']['Male']);
        $this->assertSame(2, $report['summaries']['epi']['dosesBySex']['Female']);
        $this->assertSame(3, $report['summaries']['epi']['dosesBySex']['Total']);
    }

    public function test_fp_summaries_sex_disaggregation(): void
    {
        $user = $this->authorizedUser();
        $workerId = $this->healthWorkerFor($user);

        $male = $this->patient('MaleFP', 1);
        $male->update(['sex' => 'Male']);
        $female = $this->patient('FemaleFP', 1);
        $female->update(['sex' => 'Female']);

        FamilyPlanningClient::create([
            'patient_id' => $male->id, 'type_of_client' => FamilyPlanningClient::TYPE_NEW_ACCEPTOR,
            'method' => 'Condom', 'is_active' => true, 'recorded_by' => $workerId,
        ]);
        FamilyPlanningClient::create([
            'patient_id' => $female->id, 'type_of_client' => FamilyPlanningClient::TYPE_NEW_ACCEPTOR,
            'method' => 'Pills', 'is_active' => true, 'recorded_by' => $workerId,
        ]);
        FamilyPlanningClient::create([
            'patient_id' => $female->id, 'type_of_client' => FamilyPlanningClient::TYPE_CONTINUING_USER,
            'method' => 'Injectable', 'is_active' => true, 'recorded_by' => $workerId,
        ]);

        $report = MchEpiFpReportService::query($this->filters(), $user);

        $this->assertArrayHasKey('clientsBySex', $report['summaries']['fp']);
        $this->assertSame(1, $report['summaries']['fp']['clientsBySex']['Male']);
        $this->assertSame(2, $report['summaries']['fp']['clientsBySex']['Female']);
        $this->assertSame(3, $report['summaries']['fp']['clientsBySex']['Total']);
    }

    // PR2: Natality section (M1 Section I)
    public function test_natality_summaries_included_in_merged_report(): void
    {
        $user = $this->authorizedUser();
        $workerId = $this->healthWorkerFor($user);

        // Assign zone 1 to worker so they can see patients in zone 1
        DB::table('zones')->where('id', 1)->update(['assigned_worker_id' => $workerId]);

        // Create live births with mother age bands
        $youngMom = $this->patient('YoungMom', 1);
        $youngMom->update(['sex' => 'Female', 'date_of_birth' => now()->subYears(14)->toDateString()]);
        $adultMom = $this->patient('AdultMom', 1);
        $adultMom->update(['sex' => 'Female', 'date_of_birth' => now()->subYears(17)->toDateString()]);
        $olderMom = $this->patient('OlderMom', 1);
        $olderMom->update(['sex' => 'Female', 'date_of_birth' => now()->subYears(30)->toDateString()]);

        $preg1 = $this->pregnancy($youngMom, $workerId);
        $preg2 = $this->pregnancy($adultMom, $workerId);
        $preg3 = $this->pregnancy($olderMom, $workerId);

        foreach ([$preg1, $preg2, $preg3] as $pregId) {
            DB::table('postnatal_records')->insert([
                'patient_id' => DB::table('pregnancies')->where('id', $pregId)->value('patient_id'),
                'pregnancy_id' => $pregId,
                'pregnancy_outcome' => 'live_birth',
                'place_delivered' => 'health_center',
                'mode_of_delivery' => 'normal_vaginal',
                'attendant_at_birth' => 'midwife',
                'delivery_date' => now()->toDateString(),
                'delivery_time' => '09:30:00',
                'breastfeeding_date' => now()->toDateString(),
                'breastfeeding_time' => '10:00:00',
                'recorded_by' => $workerId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        ApplicationSetting::set('projected_population', '50000');

        $report = MchEpiFpReportService::query($this->filters(), $user);

        $this->assertArrayHasKey('natality', $report['summaries']);
        $this->assertSame(3, $report['summaries']['natality']['liveBirths']);
        $this->assertArrayHasKey('byMotherAge', $report['summaries']['natality']);
        $this->assertSame(1, $report['summaries']['natality']['byMotherAge']['10-14']);
        $this->assertSame(1, $report['summaries']['natality']['byMotherAge']['15-19']);
        $this->assertSame(1, $report['summaries']['natality']['byMotherAge']['20-49']);
        $this->assertArrayHasKey('cbr', $report['summaries']['natality']);
        // CBR = 3 / 50000 * 1000 = 0.06 -> rounded or as float
        $this->assertEqualsWithDelta(0.06, $report['summaries']['natality']['cbr'], 0.01);
    }

    // PR2: CSV export routes
    public function test_morbidity_csv_export_requires_reports_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('reports.morbidity.export.csv', ['month' => now()->month, 'year' => now()->year]))
            ->assertForbidden();
    }

    public function test_morbidity_csv_export_returns_csv(): void
    {
        $user = $this->authorizedUser();
        $workerId = $this->healthWorkerFor($user);

        // Assign zone 1 to worker so they can see patients in zone 1
        DB::table('zones')->where('id', 1)->update(['assigned_worker_id' => $workerId]);

        $patient = $this->patient();

        $consultationId = DB::table('consultations')->insertGetId([
            'patient_id' => $patient->id, 'worker_id' => $workerId, 'attending_doctor_id' => $workerId,
            'status' => 'completed', 'nature_of_visit' => 'New Consultation/Case', 'mode_of_transaction' => 'Walk-in',
            'complaint_text' => 'Fever', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $diagId = DB::table('diagnosis_lookup')->insertGetId([
            'diagnosis_code' => 'J06.9', 'diagnosis_name' => 'Acute upper respiratory infection',
        ]);
        DB::table('diagnosis_records')->insert([
            'consultation_id' => $consultationId, 'diagnosis_id' => $diagId,
            'diagnosed_by' => $workerId, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.morbidity.export.csv', ['month' => now()->month, 'year' => now()->year]));

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertHeaderContains('Content-Disposition', 'attachment; filename=');
    }

    public function test_mch_epi_fp_csv_export_requires_reports_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('reports.mch-epi-fp.export.csv', $this->filters()))
            ->assertForbidden();
    }

    public function test_mch_epi_fp_csv_export_returns_csv(): void
    {
        $user = $this->authorizedUser();
        $workerId = $this->healthWorkerFor($user);

        // Assign zone 1 to worker so they can see patients in zone 1
        DB::table('zones')->where('id', 1)->update(['assigned_worker_id' => $workerId]);

        $patient = $this->patient();

        $this->pregnancy($patient, $workerId);
        DB::table('prenatal_visits')->insert([
            'pregnancy_id' => 1, 'visit_date' => now()->toDateString(), 'recorded_by' => $workerId,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.mch-epi-fp.export.csv', $this->filters()));

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertHeaderContains('Content-Disposition', 'attachment; filename=');
        // Content verification for streamed CSV is done via manual testing;
        // the route returns correct headers and status.
    }

    // PR3: Morbidity M2 Matrix
    public function test_morbidity_matrix_returns_correct_structure(): void
    {
        $user = $this->authorizedUser();
        $workerId = $this->healthWorkerFor($user);

        // Assign zone 1 to worker so they can see patients in zone 1
        DB::table('zones')->where('id', 1)->update(['assigned_worker_id' => $workerId]);

        // Create test diagnoses for different age groups and sexes
        $maleInfant = $this->patient('MaleInfant', 1);
        $maleInfant->update(['sex' => 'Male', 'date_of_birth' => now()->subDays(3)->toDateString()]);
        $femaleInfant = $this->patient('FemaleInfant', 1);
        $femaleInfant->update(['sex' => 'Female', 'date_of_birth' => now()->subDays(10)->toDateString()]);
        $maleChild = $this->patient('MaleChild', 1);
        $maleChild->update(['sex' => 'Male', 'date_of_birth' => now()->subYears(3)->toDateString()]);
        $femaleAdult = $this->patient('FemaleAdult', 1);
        $femaleAdult->update(['sex' => 'Female', 'date_of_birth' => now()->subYears(30)->toDateString()]);
        $maleSenior = $this->patient('MaleSenior', 1);
        $maleSenior->update(['sex' => 'Male', 'date_of_birth' => now()->subYears(75)->toDateString()]);

        $diagId = DB::table('diagnosis_lookup')->insertGetId([
            'diagnosis_code' => 'J06.9', 'diagnosis_name' => 'Acute upper respiratory infection',
        ]);

        foreach ([$maleInfant, $femaleInfant, $maleChild, $femaleAdult, $maleSenior] as $patient) {
            $consultationId = DB::table('consultations')->insertGetId([
                'patient_id' => $patient->id, 'worker_id' => $workerId, 'attending_doctor_id' => $workerId,
                'status' => 'completed', 'nature_of_visit' => 'New Consultation/Case', 'mode_of_transaction' => 'Walk-in',
                'complaint_text' => 'Test', 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('diagnosis_records')->insert([
                'consultation_id' => $consultationId, 'diagnosis_id' => $diagId,
                'diagnosed_by' => $workerId, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        ApplicationSetting::set('projected_population', '10000');

        $matrix = MorbidityReportService::matrix(now()->month, now()->year, null, null, 'all', $user);

        // Matrix should have diagnosis rows with age/sex buckets
        $this->assertArrayHasKey('rows', $matrix);
        $this->assertArrayHasKey('ageBands', $matrix);
        $this->assertArrayHasKey('sexLabels', $matrix);
        $this->assertCount(1, $matrix['rows']); // One diagnosis (J06.9)

        $row = $matrix['rows'][0];
        $this->assertSame('J06.9', $row->diagnosis_code);
        $this->assertSame('Acute upper respiratory infection', $row->diagnosis_name);
        $this->assertEquals(5, $row->total_cases); // 5 test patients

        // Check age bands include M2 buckets
        $expectedBands = ['0-6d', '7-28d', '29d-11m', '1-4', '5-9', '10-14', '15-19', '20-24', '25-29', '30-34', '35-39', '40-44', '45-49', '50-54', '55-59', '60-64', '65-69', '70-74', '75-79', '80+'];
        foreach ($expectedBands as $band) {
            $this->assertContains($band, $matrix['ageBands']);
        }
        $this->assertSame(['Male', 'Female', 'Total'], $matrix['sexLabels']);
    }

    public function test_morbidity_leading_causes_sections_exist(): void
    {
        $user = $this->authorizedUser();
        $workerId = $this->healthWorkerFor($user);

        DB::table('zones')->where('id', 1)->update(['assigned_worker_id' => $workerId]);

        // Create multiple diagnoses with different case counts
        $diag1 = DB::table('diagnosis_lookup')->insertGetId([
            'diagnosis_code' => 'J06.9', 'diagnosis_name' => 'Acute upper respiratory infection',
        ]);
        $diag2 = DB::table('diagnosis_lookup')->insertGetId([
            'diagnosis_code' => 'A09.9', 'diagnosis_name' => 'Diarrhea and gastroenteritis',
        ]);
        $diag3 = DB::table('diagnosis_lookup')->insertGetId([
            'diagnosis_code' => 'I10', 'diagnosis_name' => 'Essential hypertension',
        ]);

        $patients = [];
        foreach (['P1', 'P2', 'P3', 'P4', 'P5'] as $i => $name) {
            $p = $this->patient($name, 1);
            $p->update(['sex' => $i % 2 === 0 ? 'Male' : 'Female', 'date_of_birth' => now()->subYears(25)->toDateString()]);
            $patients[] = $p;
        }

        // 3 cases of J06.9
        foreach ([$patients[0], $patients[1], $patients[2]] as $p) {
            $cId = DB::table('consultations')->insertGetId([
                'patient_id' => $p->id, 'worker_id' => $workerId, 'attending_doctor_id' => $workerId,
                'status' => 'completed', 'nature_of_visit' => 'New', 'mode_of_transaction' => 'Walk-in',
                'complaint_text' => 'Test', 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('diagnosis_records')->insert([
                'consultation_id' => $cId, 'diagnosis_id' => $diag1, 'diagnosed_by' => $workerId,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        // 2 cases of A09.9
        foreach ([$patients[3], $patients[4]] as $p) {
            $cId = DB::table('consultations')->insertGetId([
                'patient_id' => $p->id, 'worker_id' => $workerId, 'attending_doctor_id' => $workerId,
                'status' => 'completed', 'nature_of_visit' => 'New', 'mode_of_transaction' => 'Walk-in',
                'complaint_text' => 'Test', 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('diagnosis_records')->insert([
                'consultation_id' => $cId, 'diagnosis_id' => $diag2, 'diagnosed_by' => $workerId,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        // 1 case of I10
        $cId = DB::table('consultations')->insertGetId([
            'patient_id' => $patients[0]->id, 'worker_id' => $workerId, 'attending_doctor_id' => $workerId,
            'status' => 'completed', 'nature_of_visit' => 'New', 'mode_of_transaction' => 'Walk-in',
            'complaint_text' => 'Test', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('diagnosis_records')->insert([
            'consultation_id' => $cId, 'diagnosis_id' => $diag3, 'diagnosed_by' => $workerId,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        ApplicationSetting::set('projected_population', '10000');

        $sections = MorbidityReportService::leadingCauses(now()->month, now()->year, null, null, $user);

        // Should have sections A.2.a through A.2.g
        $expectedSections = [
            'all_ages' => 'A.2.a',
            'males' => 'A.2.b',
            'females' => 'A.2.c',
            'under_5' => 'A.2.d',
            'adolescents_10_19' => 'A.2.e',
            'seniors_60_plus' => 'A.2.f',
            // 'all_ages_by_rate' => 'A.2.g' - rate per 100k
        ];

        foreach ($expectedSections as $key => $label) {
            $this->assertArrayHasKey($key, $sections);
            $this->assertArrayHasKey('label', $sections[$key]);
            $this->assertArrayHasKey('rows', $sections[$key]);
        }

        // All ages: J06.9 (3), A09.9 (2), I10 (1)
        $allAges = $sections['all_ages']['rows'];
        $this->assertSame('J06.9', $allAges[0]->diagnosis_code);
        $this->assertSame(3, $allAges[0]->case_count);
        $this->assertSame('A09.9', $allAges[1]->diagnosis_code);
        $this->assertSame(2, $allAges[1]->case_count);

        // Rate section should have rate_per_100k
        $rateSection = $sections['all_ages_by_rate'];
        $this->assertObjectHasProperty('rate_per_100k', $rateSection['rows'][0]);
    }

    public function test_morbidity_screen_renders_m2_matrix_and_leading_causes(): void
    {
        $user = $this->authorizedUser();
        $workerId = $this->healthWorkerFor($user);
        DB::table('zones')->where('id', 1)->update(['assigned_worker_id' => $workerId]);

        $patient = $this->patient('Test', 1);
        $patient->update(['sex' => 'Male', 'date_of_birth' => now()->subYears(5)->toDateString()]);

        $diagId = DB::table('diagnosis_lookup')->insertGetId([
            'diagnosis_code' => 'J06.9', 'diagnosis_name' => 'Acute upper respiratory infection',
        ]);
        $cId = DB::table('consultations')->insertGetId([
            'patient_id' => $patient->id, 'worker_id' => $workerId, 'attending_doctor_id' => $workerId,
            'status' => 'completed', 'nature_of_visit' => 'New', 'mode_of_transaction' => 'Walk-in',
            'complaint_text' => 'Test', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('diagnosis_records')->insert([
            'consultation_id' => $cId, 'diagnosis_id' => $diagId, 'diagnosed_by' => $workerId,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        ApplicationSetting::set('projected_population', '10000');

        $this->actingAs($user)
            ->get(route('reports.morbidity', ['month' => now()->month, 'year' => now()->year]))
            ->assertOk()
            ->assertSee('M2 Matrix')
            ->assertSee('Leading Causes')
            ->assertSee('A.2.a')
            ->assertSee('A.2.b')
            ->assertSee('A.2.c')
            ->assertSee('A.2.d')
            ->assertSee('A.2.e')
            ->assertSee('A.2.f')
            ->assertSee('Rate per 100,000');
    }

    public function test_morbidity_pdf_renders_m2_matrix(): void
    {
        $user = $this->authorizedUser();
        $workerId = $this->healthWorkerFor($user);
        DB::table('zones')->where('id', 1)->update(['assigned_worker_id' => $workerId]);

        $patient = $this->patient('Test', 1);
        $patient->update(['sex' => 'Male', 'date_of_birth' => now()->subYears(5)->toDateString()]);

        $diagId = DB::table('diagnosis_lookup')->insertGetId([
            'diagnosis_code' => 'J06.9', 'diagnosis_name' => 'Acute upper respiratory infection',
        ]);
        $cId = DB::table('consultations')->insertGetId([
            'patient_id' => $patient->id, 'worker_id' => $workerId, 'attending_doctor_id' => $workerId,
            'status' => 'completed', 'nature_of_visit' => 'New', 'mode_of_transaction' => 'Walk-in',
            'complaint_text' => 'Test', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('diagnosis_records')->insert([
            'consultation_id' => $cId, 'diagnosis_id' => $diagId, 'diagnosed_by' => $workerId,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        ApplicationSetting::set('projected_population', '10000');

        $this->actingAs($user)
            ->get(route('reports.morbidity.download', ['month' => now()->month, 'year' => now()->year]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_morbidity_rate_calculation_uses_projected_population(): void
    {
        $user = $this->authorizedUser();
        $workerId = $this->healthWorkerFor($user);
        DB::table('zones')->where('id', 1)->update(['assigned_worker_id' => $workerId]);

        $patient = $this->patient('Test', 1);
        $patient->update(['sex' => 'Male', 'date_of_birth' => now()->subYears(5)->toDateString()]);

        $diagId = DB::table('diagnosis_lookup')->insertGetId([
            'diagnosis_code' => 'J06.9', 'diagnosis_name' => 'Acute upper respiratory infection',
        ]);
        $cId = DB::table('consultations')->insertGetId([
            'patient_id' => $patient->id, 'worker_id' => $workerId, 'attending_doctor_id' => $workerId,
            'status' => 'completed', 'nature_of_visit' => 'New', 'mode_of_transaction' => 'Walk-in',
            'complaint_text' => 'Test', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('diagnosis_records')->insert([
            'consultation_id' => $cId, 'diagnosis_id' => $diagId, 'diagnosed_by' => $workerId,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        ApplicationSetting::set('projected_population', '50000');

        $sections = MorbidityReportService::leadingCauses(now()->month, now()->year, null, null, $user);

        // 1 case in 50,000 population = 2 per 100,000
        $rate = $sections['all_ages_by_rate']['rows'][0]->rate_per_100k ?? 0;
        $this->assertEqualsWithDelta(2.0, $rate, 0.1);
    }

    public function test_morbidity_matrix_zone_scoping_works(): void
    {
        $user = $this->createUserWithPermissions(['reports', 'household']);
        $workerId = $this->healthWorkerFor($user);

        DB::table('zones')->insertOrIgnore([
            'id' => 2, 'zone_number' => 'Zone 2', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('zones')->where('id', 1)->update(['assigned_worker_id' => $workerId]);

        $ownPatient = $this->patient('OwnZone', 1);
        $otherPatient = $this->patient('OtherZone', 2);

        $diagId = DB::table('diagnosis_lookup')->insertGetId([
            'diagnosis_code' => 'J06.9', 'diagnosis_name' => 'Acute upper respiratory infection',
        ]);

        foreach ([$ownPatient, $otherPatient] as $p) {
            $cId = DB::table('consultations')->insertGetId([
                'patient_id' => $p->id, 'worker_id' => $workerId, 'attending_doctor_id' => $workerId,
                'status' => 'completed', 'nature_of_visit' => 'New', 'mode_of_transaction' => 'Walk-in',
                'complaint_text' => 'Test', 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('diagnosis_records')->insert([
                'consultation_id' => $cId, 'diagnosis_id' => $diagId, 'diagnosed_by' => $workerId,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        ApplicationSetting::set('projected_population', '10000');

        $matrix = MorbidityReportService::matrix(now()->month, now()->year, null, null, 'all', $user);

        $this->assertSame(1, $matrix['rows'][0]->total_cases); // Only own zone
        $this->assertSame('Acute upper respiratory infection', $matrix['rows'][0]->diagnosis_name);
    }

    // ============================================================
    // PR4: Death Model Tests (4a)
    // ============================================================

    public function test_death_model_can_be_created_with_required_fields(): void
    {
        $user = $this->authorizedUser();
        $workerId = $this->healthWorkerFor($user);
        $patient = $this->patient('Deceased', 1);
        $patient->update(['sex' => 'Female', 'date_of_birth' => now()->subYears(30)->toDateString()]);

        $death = Death::create([
            'patient_id' => $patient->id,
            'date_of_death' => now()->toDateString(),
            'place_of_death' => 'home',
            'cause_diagnosis_id' => null,
            'cause_text' => 'Cardiac arrest',
            'maternal_death' => false,
            'perinatal_death' => false,
            'neonatal_death' => false,
            'infant_death' => false,
            'under_five_death' => false,
            'recorded_by' => $workerId,
        ]);

        $this->assertDatabaseHas('deaths', [
            'patient_id' => $patient->id,
            'cause_text' => 'Cardiac arrest',
            'maternal_death' => false,
        ]);
        $this->assertSame($patient->id, $death->patient_id);
        $this->assertSame($workerId, $death->recorded_by);
    }

    public function test_death_model_audit_log_on_create(): void
    {
        $user = $this->authorizedUser();
        $workerId = $this->healthWorkerFor($user);
        $patient = $this->patient('AuditTest', 1);

        Death::create([
            'patient_id' => $patient->id,
            'date_of_death' => now()->toDateString(),
            'place_of_death' => 'hospital',
            'cause_text' => 'Sepsis',
            'recorded_by' => $workerId,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'table_name' => 'deaths',
        ]);
    }

    public function test_death_record_requires_date_and_place(): void
    {
        $user = $this->createUserWithPermissions(['reports', 'patients']);
        $workerId = $this->healthWorkerFor($user);
        $patient = $this->patient('Test', 1);

        // Should fail validation without date_of_death
        $response = $this->actingAs($user)
            ->post(route('deaths.store', $patient), [
                'place_of_death' => 'home',
                'cause_text' => 'Unknown',
                'recorded_by' => $workerId,
            ]);
        $response->assertSessionHasErrors('date_of_death');

        // Should fail validation without place_of_death
        $response = $this->actingAs($user)
            ->post(route('deaths.store', $patient), [
                'date_of_death' => now()->toDateString(),
                'cause_text' => 'Unknown',
                'recorded_by' => $workerId,
            ]);
        $response->assertSessionHasErrors('place_of_death');
    }

    public function test_mortality_section_h_rendered_in_merged_report(): void
    {
        $user = $this->authorizedUser();
        $workerId = $this->healthWorkerFor($user);
        DB::table('zones')->where('id', 1)->update(['assigned_worker_id' => $workerId]);

        // Create a death record
        $patient = $this->patient('Mortality', 1);
        $patient->update(['sex' => 'Female', 'date_of_birth' => now()->subYears(25)->toDateString()]);
        $pregId = $this->pregnancy($patient, $workerId);

        Death::create([
            'patient_id' => $patient->id,
            'date_of_death' => now()->toDateString(),
            'place_of_death' => 'hospital',
            'cause_diagnosis_id' => null,
            'cause_text' => 'Postpartum hemorrhage',
            'maternal_death' => true,
            'perinatal_death' => false,
            'neonatal_death' => false,
            'infant_death' => false,
            'under_five_death' => false,
            'recorded_by' => $workerId,
        ]);

        ApplicationSetting::set('projected_population', '10000');

        $report = MchEpiFpReportService::query($this->filters(), $user);

        $this->assertArrayHasKey('mortality', $report['summaries']);
        $mortality = $report['summaries']['mortality'];
        $this->assertSame(1, $mortality['totalDeaths']);
        $this->assertSame(1, $mortality['maternalDeaths']);
        $this->assertSame(0, $mortality['underFiveDeaths']);
        $this->assertSame(0, $mortality['infantDeaths']);
        $this->assertSame(0, $mortality['neonatalDeaths']);
        $this->assertSame(0, $mortality['perinatalDeaths']);
    }

    public function test_death_record_ui_on_patient_profile(): void
    {
        $user = $this->createUserWithPermissions(['reports', 'patients']);
        $workerId = $this->healthWorkerFor($user);
        DB::table('zones')->where('id', 1)->update(['assigned_worker_id' => $workerId]);

        $patient = $this->patient('DeathUI', 1);
        $patient->update(['sex' => 'Female', 'date_of_birth' => now()->subYears(30)->toDateString()]);

        // Create death record
        Death::create([
            'patient_id' => $patient->id,
            'date_of_death' => now()->toDateString(),
            'place_of_death' => 'hospital',
            'cause_text' => 'Eclampsia',
            'maternal_death' => true,
            'recorded_by' => $workerId,
        ]);

        $this->actingAs($user)
            ->get(route('patients.show', $patient->id))
            ->assertOk()
            ->assertSee('Death Record')
            ->assertSee('Eclampsia')
            ->assertSee('Maternal death');
    }
}
