<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Database\Seeders\HouseholdMemberSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Guards the seeders against the household schema additions:
 * households.head_first_name/head_middle_name (add_head_name_fields
 * migration) and the household_members table (create_household_members
 * migration). Without these the index shows surname-only heads and every
 * Residents count reads 0.
 */
class HouseholdMemberSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_households_have_head_first_names(): void
    {
        $this->seed(DatabaseSeeder::class);

        $missingHeadName = DB::table('households')
            ->whereNull('head_first_name')
            ->count();

        $this->assertSame(0, $missingHeadName, 'Every seeded household must have a head_first_name.');
    }

    public function test_every_seeded_patient_gets_a_household_member_row(): void
    {
        $this->seed(DatabaseSeeder::class);

        $expected = DB::table('patients')
            ->whereNull('deleted_at')
            ->whereNotNull('household_id')
            ->count();

        $actual = DB::table('household_members')
            ->whereNull('deleted_at')
            ->whereNotNull('patient_id')
            ->count();

        $this->assertGreaterThan(0, $expected, 'Seed should create patients.');
        $this->assertSame($expected, $actual, 'Each seeded patient must be backfilled into household_members.');
    }

    public function test_household_member_seeder_is_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);

        $before = DB::table('household_members')->count();

        $this->seed(HouseholdMemberSeeder::class);

        $this->assertSame($before, DB::table('household_members')->count());
    }
}
