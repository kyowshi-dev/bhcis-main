<?php

namespace Tests\Feature;

use App\Helpers\HouseholdHelper;
use App\Models\Household;
use App\Models\HouseholdMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class HouseholdShowAgeRegressionTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    public function test_show_page_renders_for_member_with_age_only_no_dob(): void
    {
        $user = $this->createUserWithNamedRole('Admin', ['household', 'users']);

        DB::table('zones')->insertGetId([
            'zone_number' => 'Z-Age', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $zoneId = DB::table('zones')->value('id');

        $household = Household::create([
            'zone_id' => $zoneId,
            'head_last_name' => 'AgeOnly',
            'head_first_name' => 'Test',
        ]);

        $member = HouseholdMember::create([
            'household_id' => $household->id,
            'first_name' => 'Age',
            'last_name' => 'Only',
            'sex' => 'Male',
            'age' => 13,
            'relationship' => 'Son',
        ]);

        $this->assertNull($member->date_of_birth);
        $this->assertSame(13, $member->age);

        $withDob = HouseholdMember::create([
            'household_id' => $household->id,
            'first_name' => 'Dob',
            'last_name' => 'Only',
            'sex' => 'Female',
            'date_of_birth' => now()->subYears(30)->toDateString(),
            'relationship' => 'Spouse',
        ]);
        $this->assertSame(30, $withDob->age);

        $this->actingAs($user)
            ->get(route('households.show', $household->id))
            ->assertOk()
            ->assertSee('13', false)
            ->assertSee('30', false);
    }

    public function test_soft_deleted_member_is_not_counted_and_show_still_renders(): void
    {
        $user = $this->createUserWithNamedRole('Admin', ['household', 'users']);

        DB::table('zones')->insertGetId([
            'zone_number' => 'Z-Del', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $zoneId = DB::table('zones')->value('id');

        $household = Household::create([
            'zone_id' => $zoneId,
            'head_last_name' => 'Deleted',
            'head_first_name' => 'Test',
        ]);

        $deleted = HouseholdMember::create([
            'household_id' => $household->id,
            'first_name' => 'Gone',
            'last_name' => 'Member',
            'sex' => 'Female',
            'date_of_birth' => '2010-01-01',
            'relationship' => 'Daughter',
        ]);
        $deleted->delete();

        $response = $this->actingAs($user)->get(route('households.show', $household->id));
        $response->assertOk();
        $response->assertSee('0', false); // residents count 0
        $response->assertDontSee('Gone');
        $this->assertSame(0, HouseholdHelper::getMemberCount($household->id));
    }
}
