<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class HouseholdCreateTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('permissions')->insertOrIgnore([
            'name' => 'household',
            'description' => 'Access to Households module',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function authorizedUser(): User
    {
        $user = $this->createUserWithPermissions(['household']);

        $workerId = DB::table('health_workers')->insertGetId([
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => 'BHW',
            'role' => 'BHW',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('zones')->insert(['id' => 1, 'zone_number' => '1', 'assigned_worker_id' => $workerId]);
        DB::table('zones')->insert(['id' => 2, 'zone_number' => '2', 'assigned_worker_id' => $workerId]);

        return $user;
    }

    public function test_create_page_loads_with_zones_for_authorized_user(): void
    {
        $this->actingAs($this->authorizedUser());

        $response = $this->get(route('households.create'));
        $response->assertOk();
        $response->assertSee('Zone 1');
        $response->assertSee('Zone 2');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('households.create'))->assertRedirect(route('login'));
    }

    public function test_store_creates_household(): void
    {
        $this->actingAs($this->authorizedUser());

        $this->post(route('households.store'), [
            'zone_id' => 1,
            'head_first_name' => 'Juan',
            'head_middle_name' => 'Santos',
            'head_last_name' => 'Dela Cruz',
            'head_suffix' => 'Jr.',
            'contact_number' => '+63-9171234567',
        ])->assertRedirect(route('households.index'));

        $this->assertDatabaseHas('households', [
            'zone_id' => 1,
            'head_first_name' => 'Juan',
            'head_middle_name' => 'Santos',
            'head_last_name' => 'Dela Cruz',
            'head_suffix' => 'Jr.',
            'contact_number' => '+63-9171234567',
        ]);
    }

    public function test_members_json_returns_household_and_members(): void
    {
        $this->actingAs($this->authorizedUser());

        $householdId = DB::table('households')->insertGetId([
            'zone_id' => 1,
            'head_first_name' => 'Juan',
            'head_last_name' => 'Dela Cruz',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('household_members')->insert([
            'household_id' => $householdId,
            'first_name' => 'Maria',
            'last_name' => 'Dela Cruz',
            'sex' => 'Female',
            'date_of_birth' => '2000-01-01',
            'relationship' => 'Spouse',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson(route('households.members', $householdId));

        $response->assertOk();
        $response->assertJsonPath('household.id', $householdId);
        $response->assertJsonCount(1, 'members');
        $response->assertJsonPath('members.0.first_name', 'Maria');
    }

    public function test_members_json_denies_household_outside_assigned_zones(): void
    {
        $user = $this->authorizedUser();

        $otherZoneId = DB::table('zones')->insertGetId([
            'zone_number' => '99',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $householdId = DB::table('households')->insertGetId([
            'zone_id' => $otherZoneId,
            'head_first_name' => 'Outside',
            'head_last_name' => 'Zone',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->getJson(route('households.members', $householdId))
            ->assertStatus(403);
    }
}
