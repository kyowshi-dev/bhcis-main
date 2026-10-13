<?php

namespace App\Services;

use App\Models\Patient;
use Illuminate\Support\Facades\DB;

final class HouseholdMemberBackfillService
{
    /**
     * Backfill household_members from existing patients.
     * Idempotent: skips patients that already have a member row linked via patient_id.
     *
     * @return int number of members created
     */
    public static function backfill(): int
    {
        $patients = Patient::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->whereNotNull('household_id')
            ->get();

        $created = 0;

        foreach ($patients as $patient) {
            $exists = DB::table('household_members')
                ->where('patient_id', $patient->id)
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                continue;
            }

            $relationship = self::mapFamilyRelationship($patient->family_relationship);

            DB::table('household_members')->insert([
                'household_id' => $patient->household_id,
                'patient_id' => $patient->id,
                'first_name' => $patient->first_name,
                'middle_name' => $patient->middle_name,
                'last_name' => $patient->last_name,
                'suffix' => $patient->suffix,
                'sex' => $patient->sex,
                'date_of_birth' => $patient->date_of_birth,
                'age' => null,
                'relationship' => $relationship,
                'created_at' => $patient->created_at,
                'updated_at' => $patient->updated_at,
            ]);

            $created++;
        }

        return $created;
    }

    private static function mapFamilyRelationship(?string $rel): string
    {
        return match ($rel) {
            'Father' => 'Father',
            'Mother' => 'Mother',
            'Son' => 'Son',
            'Daughter' => 'Daughter',
            default => 'Other',
        };
    }
}
