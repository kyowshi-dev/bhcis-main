<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

final class HouseholdService
{
    public static function findOrFail(int $id): object
    {
        $household = DB::table('households')->where('id', $id)->first();

        if (! $household) {
            abort(404, 'Household not found');
        }

        return $household;
    }

    public static function create(array $validated): int
    {
        return DB::table('households')->insertGetId([
            'zone_id' => $validated['zone_id'],
            'head_first_name' => ucwords(strtolower($validated['head_first_name'])),
            'head_middle_name' => self::normalizeName($validated['head_middle_name'] ?? null),
            'head_last_name' => ucwords(strtolower($validated['head_last_name'])),
            'head_suffix' => self::trimOrNull($validated['head_suffix'] ?? null),
            'contact_number' => $validated['contact_number'] !== null ? trim($validated['contact_number']) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function update(int $id, array $validated): void
    {
        DB::table('households')->where('id', $id)->update([
            'zone_id' => $validated['zone_id'],
            'head_first_name' => ucwords(strtolower($validated['head_first_name'])),
            'head_middle_name' => self::normalizeName($validated['head_middle_name'] ?? null),
            'head_last_name' => ucwords(strtolower($validated['head_last_name'])),
            'head_suffix' => self::trimOrNull($validated['head_suffix'] ?? null),
            'contact_number' => $validated['contact_number'] !== null ? trim($validated['contact_number']) : null,
            'updated_at' => now(),
        ]);
    }

    private static function normalizeName(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return ucwords(strtolower(trim($value)));
    }

    private static function trimOrNull(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return trim($value);
    }

    public static function reassignZones(array $ids, int $newZoneId): void
    {
        DB::table('households')
            ->whereIn('id', $ids)
            ->update([
                'zone_id' => $newZoneId,
                'updated_at' => now(),
            ]);
    }
}
