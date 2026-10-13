<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\LocationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class SearchLocationsTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    private function userWithPermissions(array $permissions): User
    {
        return $this->createUserWithPermissions($permissions);
    }

    private function seedLocations(): void
    {
        $misamisOriental = DB::table('provinces')->insertGetId([
            'psgc_code' => '104300000',
            'name' => 'Misamis Oriental',
        ]);

        $lanaoDelNorte = DB::table('provinces')->insertGetId([
            'psgc_code' => '103500000',
            'name' => 'Lanao Del Norte',
        ]);

        DB::table('municipalities')->insert([
            ['psgc_code' => '104324000', 'province_id' => $misamisOriental, 'name' => 'Tagoloan', 'is_capital' => false],
            ['psgc_code' => '104301000', 'province_id' => $misamisOriental, 'name' => 'Alubijid', 'is_capital' => false],
            ['psgc_code' => '104300000', 'province_id' => $misamisOriental, 'name' => 'City of Cagayan De Oro', 'is_capital' => true],
            ['psgc_code' => '103520000', 'province_id' => $lanaoDelNorte, 'name' => 'Tagoloan', 'is_capital' => false],
        ]);
    }

    public function test_location_search_matches_city_and_returns_province_as_subtext(): void
    {
        $this->seedLocations();

        $response = $this->actingAs($this->userWithPermissions(['patients']))
            ->getJson('/search/locations?query=Tagoloan');

        $response->assertOk()
            ->assertJsonFragment(['text' => 'Tagoloan', 'subtext' => 'Misamis Oriental'])
            ->assertJsonFragment(['text' => 'Tagoloan', 'subtext' => 'Lanao Del Norte']);
    }

    public function test_location_search_matches_by_province_name_with_capital_first(): void
    {
        $this->seedLocations();

        $response = $this->actingAs($this->userWithPermissions(['patients']))
            ->getJson('/search/locations?query=Misamis Oriental');

        $response->assertOk();

        $results = $response->json();
        $this->assertSame('City of Cagayan De Oro', $results[0]['text']);
        $this->assertSame('Misamis Oriental', $results[0]['subtext']);
        $this->assertContains('Alubijid', array_column($results, 'text'));
    }

    public function test_location_search_returns_empty_array_for_blank_query(): void
    {
        $this->actingAs($this->userWithPermissions(['patients']))
            ->getJson('/search/locations?query=')
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_location_search_requires_authentication(): void
    {
        $this->getJson('/search/locations?query=Tagoloan')
            ->assertUnauthorized();

        $this->get('/search/locations?query=Tagoloan')
            ->assertRedirect(route('login'));
    }

    public function test_location_search_forbidden_without_an_applicable_permission(): void
    {
        $this->actingAs($this->userWithPermissions(['household']))
            ->getJson('/search/locations?query=Tagoloan')
            ->assertForbidden();
    }

    public function test_location_search_allowed_for_batch_entry_permission(): void
    {
        $this->actingAs($this->userWithPermissions(['nurse_batch_entry']))
            ->getJson('/search/locations?query=Tagoloan')
            ->assertOk();
    }

    public function test_location_seeder_loads_the_committed_dataset(): void
    {
        $this->seed(LocationSeeder::class);

        $this->assertGreaterThan(80, DB::table('provinces')->count());
        $this->assertGreaterThan(1500, DB::table('municipalities')->count());
        $this->assertNotNull(
            DB::table('municipalities')
                ->join('provinces', 'provinces.id', '=', 'municipalities.province_id')
                ->where('municipalities.name', 'Tagoloan')
                ->where('provinces.name', 'Misamis Oriental')
                ->value('municipalities.id')
        );
    }

    public function test_patient_store_accepts_a_composed_birth_place_value(): void
    {
        DB::table('zones')->insertOrIgnore(['id' => 1, 'zone_number' => 'Zone 1']);

        $this->actingAs($this->userWithPermissions(['patients']))
            ->post(route('patients.store'), [
                'create_new_household' => 1,
                'new_household_zone_id' => 1,
                'new_household_head_first_name' => 'Juan',
                'new_household_head_last_name' => 'Dela Cruz',
                'first_name' => 'Juan',
                'last_name' => 'Dela Cruz',
                'sex' => 'Male',
                'date_of_birth' => '1990-01-01',
                'birth_place' => 'Tagoloan, Misamis Oriental',
                'civil_status' => 'Single (Walang Asawa)',
                'mother_name' => 'Maria Dela Cruz',
                'spouse_name' => 'Rosa Dela Cruz',
                'family_relationship' => 'Son',
                'is_philhealth_member' => 'n',
                'is_pcb_member' => 'n',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            'Tagoloan, Misamis Oriental',
            DB::table('patients')->value('birth_place')
        );
    }
}
