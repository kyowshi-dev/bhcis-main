<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Patient;
use App\Models\User;
use App\Services\PatientQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class PatientArchiveIndexTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('zones')->insertOrIgnore([
            'id' => 1,
            'zone_number' => 'Zone 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createPatient(array $overrides = []): Patient
    {
        return Patient::create(array_merge([
            'household_id' => Household::create(['zone_id' => 1, 'head_last_name' => 'Dela Cruz'])->id,
            'first_name' => 'Maria',
            'last_name' => 'Dela Cruz',
            'sex' => 'Female',
            'date_of_birth' => '1990-05-10',
            'civil_status' => 'Married',
            'mother_name' => '',
            'spouse_name' => 'Juan Dela Cruz',
            'family_relationship' => 'Mother',
            'residential_address' => 'Zone 1 Sta. Ana',
        ], $overrides));
    }

    public function test_archived_patients_are_hidden_from_default_index(): void
    {
        $user = $this->createUserWithPermissions(['patients']);
        $active = $this->createPatient(['first_name' => 'ActiveUniquelyNamed']);
        $archived = $this->createPatient(['first_name' => 'ArchivedUniquelyNamed']);
        $archived->delete();

        $response = $this->actingAs($user)->get('/patients');

        $response->assertOk();
        $response->assertSee('ActiveUniquelyNamed');
        $response->assertDontSee('ArchivedUniquelyNamed');
        $response->assertViewHas('patients', function ($patients) use ($active) {
            return $patients->total() === 1 && $patients->first()->id === $active->id;
        });
        $response->assertViewHas('patientStatus', 'active');
    }

    public function test_archived_view_lists_only_archived_patients(): void
    {
        $user = $this->createUserWithPermissions(['patients', 'patients_destroy']);
        $active = $this->createPatient(['first_name' => 'ActiveUniquelyNamed']);
        $archived = $this->createPatient(['first_name' => 'ArchivedUniquelyNamed']);
        $archived->delete();

        $response = $this->actingAs($user)->get('/patients?status=archived');

        $response->assertOk();
        $response->assertSee('ArchivedUniquelyNamed');
        $response->assertDontSee('ActiveUniquelyNamed');
        $response->assertSee(route('patients.restore', $archived->id), false);
        $response->assertViewHas('patientStatus', 'archived');
        $response->assertViewHas('patients', function ($patients) use ($archived) {
            return $patients->total() === 1 && $patients->first()->id === $archived->id;
        });
    }

    public function test_index_exposes_zone_safe_status_counts(): void
    {
        $user = $this->createUserWithPermissions(['patients']);
        $this->createPatient();
        $archived = $this->createPatient();
        $archived->delete();

        $response = $this->actingAs($user)->get('/patients');

        $response->assertOk();
        $response->assertViewHas('patientCounts', ['active' => 1, 'archived' => 1]);
        $response->assertSee('Active (1)');
        $response->assertSee('Archived (1)');
    }

    public function test_legacy_trashed_param_maps_to_archived(): void
    {
        $user = $this->createUserWithPermissions(['patients']);
        $archived = $this->createPatient(['first_name' => 'LegacyArchivedName']);
        $archived->delete();

        $response = $this->actingAs($user)->get('/patients?trashed=1');

        $response->assertOk();
        $response->assertViewHas('patientStatus', 'archived');
        $response->assertSee('LegacyArchivedName');
    }

    public function test_invalid_status_is_rejected(): void
    {
        $user = $this->createUserWithPermissions(['patients']);

        $this->actingAs($user)
            ->from('/patients')
            ->get('/patients?status=bogus')
            ->assertStatus(302)
            ->assertSessionHasErrors('status');
    }

    public function test_archived_patients_are_excluded_from_prefix_search(): void
    {
        $user = $this->createUserWithPermissions(['patients']);
        $archived = $this->createPatient([
            'first_name' => 'UniqueSearchFirst',
            'last_name' => 'UniqueSearchLast',
        ]);
        $archived->delete();

        $response = $this->actingAs($user)->getJson('/search/patients?query=UniqueSearchLast');

        $response->assertOk();
        $response->assertJson([]);
    }

    public function test_archived_patients_are_excluded_from_fuzzy_search(): void
    {
        $user = $this->createUserWithPermissions(['patients']);
        // Surname does not start with the query, so the prefix pass finds
        // nothing and the fuzzy fallback path is exercised.
        $archived = $this->createPatient([
            'first_name' => 'Bernard',
            'last_name' => 'DelaCruzz',
        ]);
        $archived->delete();

        $response = $this->actingAs($user)->getJson('/search/patients?query=Cruzz');

        $response->assertOk();
        $response->assertJson([]);
    }

    public function test_last_visit_ignores_soft_deleted_consultations(): void
    {
        $patient = $this->createPatient();

        $workerUser = User::factory()->create();
        DB::table('health_workers')->insert([
            'user_id' => $workerUser->id,
            'first_name' => 'Nurse',
            'last_name' => 'Santos',
            'role' => 'Nurse',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $consultationId = DB::table('consultations')->insertGetId([
            'patient_id' => $patient->id,
            'worker_id' => 1,
            'status' => 'completed',
            'complaint_text' => 'Fever',
            'mode_of_transaction' => 'Walk-in',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $liveVisit = PatientQueryService::paginateIndex('last_visit', 'desc')
            ->first()
            ->last_visit;
        $this->assertNotNull($liveVisit);

        DB::table('consultations')->where('id', $consultationId)->update(['deleted_at' => now()]);

        $afterDelete = PatientQueryService::paginateIndex('last_visit', 'desc')
            ->first()
            ->last_visit;
        $this->assertNull($afterDelete);
    }

    public function test_archived_empty_state_shows_restore_guidance(): void
    {
        $user = $this->createUserWithPermissions(['patients']);

        $response = $this->actingAs($user)->get('/patients?status=archived');

        $response->assertOk();
        $response->assertSee('No archived patients');
        $response->assertDontSee('Register first patient');
    }

    public function test_sort_links_preserve_archived_mode(): void
    {
        $user = $this->createUserWithPermissions(['patients']);
        $archived = $this->createPatient();
        $archived->delete();

        $response = $this->actingAs($user)->get('/patients?status=archived&sort=name&dir=asc');

        $response->assertOk();
        $response->assertSee('status=archived');
        $response->assertSee('sort=age');
    }
}
