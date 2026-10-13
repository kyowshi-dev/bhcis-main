<?php

namespace Database\Seeders;

use App\Services\HouseholdMemberBackfillService;
use Illuminate\Database\Seeder;

class HouseholdMemberSeeder extends Seeder
{
    public function run(): void
    {
        $created = HouseholdMemberBackfillService::backfill();

        $this->command?->info("Backfilled {$created} household member(s) from patients.");
    }
}
