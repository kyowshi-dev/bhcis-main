<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HouseholdHelper
{
    /**
     * Get the count of members in a household.
     */
    public static function getMemberCount($householdId): int
    {
        return DB::table('household_members')
            ->where('household_id', $householdId)
            ->whereNull('deleted_at')
            ->count();
    }

    /**
     * Get the total population across multiple households.
     * Accepts either a paginated collection or array of household IDs.
     */
    public static function getTotalPopulation($households): int
    {
        if (empty($households)) {
            return 0;
        }

        // Extract IDs from paginated collection or array
        if (method_exists($households, 'pluck')) {
            $ids = $households->pluck('id')->toArray();
        } else {
            $ids = collect($households)->pluck('id')->toArray();
        }

        if (empty($ids)) {
            return 0;
        }

        return DB::table('household_members')
            ->whereIn('household_id', $ids)
            ->whereNull('deleted_at')
            ->count();
    }

    /**
     * Get counts of vulnerable groups across households.
     * Returns array with 'infants' and 'seniors' counts.
     * Infants: 0-1 years old
     * Seniors: 65+ years old
     */
    public static function getVulnerableGroupsCount($households): array
    {
        if (empty($households)) {
            return ['infants' => 0, 'seniors' => 0];
        }

        // Extract IDs
        if (method_exists($households, 'pluck')) {
            $ids = $households->pluck('id')->toArray();
        } else {
            $ids = collect($households)->pluck('id')->toArray();
        }

        if (empty($ids)) {
            return ['infants' => 0, 'seniors' => 0];
        }

        $now = Carbon::now();
        $infantThreshold = $now->copy()->subYear(); // 1 year ago = infants
        $seniorThreshold = $now->copy()->subYears(65); // 65 years ago = seniors

        // Count infants (0-1 year old) - by DOB or age fallback
        $infants = DB::table('household_members')
            ->whereIn('household_id', $ids)
            ->whereNull('deleted_at')
            ->where(function ($query) use ($infantThreshold) {
                $query->where('date_of_birth', '>', $infantThreshold)
                    ->orWhere(function ($q) {
                        $q->whereNotNull('age')
                            ->where('age', '<=', 1);
                    });
            })
            ->count();

        // Count seniors (65+) - by DOB or age fallback
        $seniors = DB::table('household_members')
            ->whereIn('household_id', $ids)
            ->whereNull('deleted_at')
            ->where(function ($query) use ($seniorThreshold) {
                $query->where('date_of_birth', '<=', $seniorThreshold)
                    ->orWhere(function ($q) {
                        $q->whereNotNull('age')
                            ->where('age', '>=', 65);
                    });
            })
            ->count();

        return [
            'infants' => $infants,
            'seniors' => $seniors,
        ];
    }

    /**
     * Get vulnerable households (those containing vulnerable group members).
     * Returns household IDs that have infants or seniors.
     */
    public static function getVulnerableHouseholdIds(): array
    {
        $now = Carbon::now();
        $infantThreshold = $now->copy()->subYear();
        $seniorThreshold = $now->copy()->subYears(65);

        return DB::table('household_members')
            ->whereNull('deleted_at')
            ->where(function ($query) use ($infantThreshold, $seniorThreshold) {
                $query->where('date_of_birth', '>', $infantThreshold)
                    ->orWhere('date_of_birth', '<=', $seniorThreshold)
                    ->orWhere(function ($q) {
                        $q->whereNotNull('age')
                            ->where(function ($q2) {
                                $q2->where('age', '<=', 1)
                                    ->orWhere('age', '>=', 65);
                            });
                    });
            })
            ->select('household_id')
            ->distinct()
            ->pluck('household_id')
            ->toArray();
    }

    /**
     * Get member count for each household in a collection.
     * Useful for adding member counts to table rows.
     */
    public static function enrichHouseholdsWithMemberCounts($households): array
    {
        $householdIds = method_exists($households, 'getCollection')
            ? $households->getCollection()->pluck('id')->toArray()
            : collect($households)->pluck('id')->toArray();

        if (empty($householdIds)) {
            return [];
        }

        $memberCounts = DB::table('household_members')
            ->whereIn('household_id', $householdIds)
            ->whereNull('deleted_at')
            ->groupBy('household_id')
            ->select('household_id', DB::raw('count(*) as member_count'))
            ->pluck('member_count', 'household_id');

        return $memberCounts->toArray();
    }
}
