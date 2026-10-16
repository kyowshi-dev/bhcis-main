<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class PatientQueryService
{
    public static function paginateIndex(string $sort, string $dir, ?User $user = null, int $perPage = 20, string $status = 'active'): LengthAwarePaginator
    {
        $query = Patient::query()
            ->join('households', 'patients.household_id', '=', 'households.id')
            ->select(
                'patients.*',
                'households.head_first_name',
                'households.head_middle_name',
                'households.head_last_name',
                'households.head_suffix',
                'households.zone_id',
                'households.contact_number'
            )
            ->selectSub(
                DB::table('consultations')
                    ->whereNull('deleted_at')
                    ->selectRaw('MAX(created_at)')
                    ->whereColumn('consultations.patient_id', 'patients.id'),
                'last_visit'
            );

        if ($status === 'archived') {
            $query->onlyTrashed();
        }

        if ($user !== null) {
            $user->scopeAccessiblePatients($query);
        }

        match ($sort) {
            'name' => $query
                ->orderBy('patients.last_name', $dir)
                ->orderBy('patients.first_name', $dir),
            'gender' => $query->orderBy('patients.sex', $dir),
            'age' => $dir === 'asc'
                ? $query->orderByDesc('patients.date_of_birth')
                : $query->orderBy('patients.date_of_birth', 'asc'),
            'last_visit' => $query
                ->orderByRaw('last_visit IS NULL ASC')
                ->orderBy('last_visit', $dir),
            'id' => $query->orderBy('patients.id', $dir),
            default => $query->orderBy('patients.created_at', $dir),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Zone-scoped counts of live vs archived patients for the index tabs.
     *
     * @return array{active: int, archived: int}
     */
    public static function statusCounts(?User $user = null): array
    {
        $active = Patient::query();
        $archived = Patient::onlyTrashed();

        if ($user !== null) {
            $user->scopeAccessiblePatients($active);
            $user->scopeAccessiblePatients($archived);
        }

        return [
            'active' => $active->count(),
            'archived' => $archived->count(),
        ];
    }
}
