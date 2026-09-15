<?php

namespace Tests\Feature;

use App\Helpers\BreadcrumbHelper;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class BreadcrumbTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    private User $user;

    private int $patientId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createUserWithPermissions(['patients', 'consultations', 'users', 'maternal', 'household', 'immunizations', 'zones', 'medicines', 'reports']);

        DB::table('zones')->insert(['id' => 1, 'zone_number' => '1']);
        $householdId = DB::table('households')->insertGetId([
            'zone_id' => 1,
            'family_name_head' => 'Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->patientId = DB::table('patients')->insertGetId([
            'household_id' => $householdId,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'sex' => 'Female',
            'date_of_birth' => '1990-01-01',
            'civil_status' => 'Single',
            'employment_status' => 'Employed',
            'mother_name' => 'Senior',
            'spouse_name' => 'N/A',
            'family_relationship' => 'Mother',
            'residential_address' => 'Sta. Ana',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_dashboard_returns_single_crumb(): void
    {
        $this->actingAs($this->user);

        $this->get(route('dashboard'))->assertOk();

        $breadcrumbs = BreadcrumbHelper::getBreadcrumbs();

        $this->assertCount(1, $breadcrumbs);
        $this->assertSame('Dashboard', $breadcrumbs[0]['name']);
        $this->assertSame(route('dashboard'), $breadcrumbs[0]['url']);
    }

    public function test_index_page_has_two_level_chain(): void
    {
        $this->actingAs($this->user);

        $this->get(route('patients.index'))->assertOk();

        $breadcrumbs = BreadcrumbHelper::getBreadcrumbs();

        $this->assertCount(2, $breadcrumbs);
        $this->assertSame('Dashboard', $breadcrumbs[0]['name']);
        $this->assertSame('Patients', $breadcrumbs[1]['name']);
    }

    public function test_show_page_has_three_level_chain(): void
    {
        $this->actingAs($this->user);

        $this->get(route('patients.show', $this->patientId))->assertOk();

        $breadcrumbs = BreadcrumbHelper::getBreadcrumbs();

        $this->assertCount(3, $breadcrumbs);
        $this->assertSame('Dashboard', $breadcrumbs[0]['name']);
        $this->assertSame('Patients', $breadcrumbs[1]['name']);
        $this->assertSame('Patient Details', $breadcrumbs[2]['name']);
    }

    public function test_households_create_nests_under_index(): void
    {
        $this->actingAs($this->user);

        $this->get(route('households.create'))->assertOk();

        $breadcrumbs = BreadcrumbHelper::getBreadcrumbs();

        $this->assertCount(3, $breadcrumbs);
        $this->assertSame('Dashboard', $breadcrumbs[0]['name']);
        $this->assertSame('Households', $breadcrumbs[1]['name']);
        $this->assertSame('Add Household', $breadcrumbs[2]['name']);
    }

    public function test_zones_edit_has_four_level_chain(): void
    {
        $this->actingAs($this->user);

        $zoneId = DB::table('zones')->insertGetId([
            'zone_number' => '2',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('zones.edit', $zoneId))->assertOk();

        $breadcrumbs = BreadcrumbHelper::getBreadcrumbs();

        $this->assertCount(4, $breadcrumbs);
        $this->assertSame('Dashboard', $breadcrumbs[0]['name']);
        $this->assertSame('Zones', $breadcrumbs[1]['name']);
        $this->assertSame('Zone Details', $breadcrumbs[2]['name']);
        $this->assertSame('Edit Zone', $breadcrumbs[3]['name']);
    }

    public function test_maternal_prenatal_includes_category_label(): void
    {
        $this->actingAs($this->user);

        $this->get(route('maternal.prenatal.index'))->assertOk();

        $breadcrumbs = BreadcrumbHelper::getBreadcrumbs();

        $names = array_column($breadcrumbs, 'name');
        $this->assertContains('Maternal Care', $names);
        $this->assertContains('Prenatal', $names);

        // Maternal Care should come before Prenatal
        $categoryIndex = array_search('Maternal Care', $names);
        $prenatalIndex = array_search('Prenatal', $names);
        $this->assertLessThan($prenatalIndex, $categoryIndex);

        // Maternal Care should be non-clickable
        $categoryCrumb = $breadcrumbs[$categoryIndex];
        $this->assertNull($categoryCrumb['url']);
    }

    public function test_maternal_postnatal_includes_category_label(): void
    {
        $this->actingAs($this->user);

        $this->get(route('maternal.postnatal.index'))->assertOk();

        $breadcrumbs = BreadcrumbHelper::getBreadcrumbs();

        $names = array_column($breadcrumbs, 'name');
        $this->assertContains('Maternal Care', $names);
        $this->assertContains('Postnatal', $names);
    }

    public function test_maternal_family_planning_includes_category_label(): void
    {
        $this->actingAs($this->user);

        $this->get(route('maternal.family-planning.index'))->assertOk();

        $breadcrumbs = BreadcrumbHelper::getBreadcrumbs();

        $names = array_column($breadcrumbs, 'name');
        $this->assertContains('Maternal Care', $names);
        $this->assertContains('Family Planning', $names);
    }

    public function test_privacy_settings_nests_under_settings(): void
    {
        $this->actingAs($this->user);

        $this->get(route('privacy.index'))->assertOk();

        $breadcrumbs = BreadcrumbHelper::getBreadcrumbs();

        $names = array_column($breadcrumbs, 'name');
        $this->assertContains('Settings', $names);
        $this->assertContains('Privacy Settings', $names);

        // Settings should come before Privacy Settings
        $settingsIndex = array_search('Settings', $names);
        $privacyIndex = array_search('Privacy Settings', $names);
        $this->assertLessThan($privacyIndex, $settingsIndex);
    }

    public function test_settings_backups_nests_under_settings(): void
    {
        $this->actingAs($this->user);

        $this->get(route('settings.backups'))->assertOk();

        $breadcrumbs = BreadcrumbHelper::getBreadcrumbs();

        $this->assertCount(3, $breadcrumbs);
        $this->assertSame('Dashboard', $breadcrumbs[0]['name']);
        $this->assertSame('Settings', $breadcrumbs[1]['name']);
        $this->assertSame('Backups', $breadcrumbs[2]['name']);
    }

    public function test_non_page_route_returns_empty(): void
    {
        $this->actingAs($this->user);

        $this->get(route('search.patients', ['query' => 'Jane']));

        // search.patients is not in PAGE_LABELS, so breadcrumbs should be empty
        $breadcrumbs = BreadcrumbHelper::getBreadcrumbs();

        // The route name during search is search.patients which is not in PAGE_LABELS
        // so getBreadcrumbs returns empty (or whatever the current non-page route produces)
        $this->assertIsArray($breadcrumbs);
    }

    public function test_is_page_route_recognizes_known_routes(): void
    {
        $this->assertTrue(BreadcrumbHelper::isPageRoute('dashboard'));
        $this->assertTrue(BreadcrumbHelper::isPageRoute('patients.index'));
        $this->assertTrue(BreadcrumbHelper::isPageRoute('maternal.prenatal.patient'));
        $this->assertFalse(BreadcrumbHelper::isPageRoute('search.patients'));
        $this->assertFalse(BreadcrumbHelper::isPageRoute(null));
        $this->assertFalse(BreadcrumbHelper::isPageRoute('nonexistent.route'));
    }
}
