<?php

namespace App\Services;

use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Read-side queries for the Activity Logs viewer.
 */
final class ActivityLogQueryService
{
    /**
     * Paginate audit log entries with filtering.
     *
     * @param  array{query?: string, user_id?: int|string, action?: string, table_name?: string, date_from?: string, date_to?: string, failures_only?: bool, sort?: string}  $filters
     */
    public static function paginateIndex(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = AuditLog::query()
            ->leftJoin('users', 'audit_logs.user_id', '=', 'users.id')
            ->select(
                'audit_logs.*',
                'users.username',
            );

        // Text search across action, table_name, record_id, username
        if (! empty($filters['query'])) {
            $q = (string) $filters['query'];
            $query->where(function ($qb) use ($q) {
                $qb->where('audit_logs.action', 'like', '%'.$q.'%')
                    ->orWhere('audit_logs.table_name', 'like', '%'.$q.'%')
                    ->orWhere('users.username', 'like', '%'.$q.'%');
                if (is_numeric($q)) {
                    $qb->orWhere('audit_logs.record_id', (int) $q);
                }
            });
        }

        // Filter by user
        if (! empty($filters['user_id'])) {
            $query->where('audit_logs.user_id', (int) $filters['user_id']);
        }

        // Filter by action
        if (! empty($filters['action'])) {
            $query->where('audit_logs.action', (string) $filters['action']);
        }

        // Filter by table name
        if (! empty($filters['table_name'])) {
            $query->where('audit_logs.table_name', (string) $filters['table_name']);
        }

        // Failure-only filter: actions ending with _failed or containing _blocked
        if (! empty($filters['failures_only'])) {
            $query->where(function ($qb) {
                $qb->where('audit_logs.action', 'like', '%_failed')
                    ->orWhere('audit_logs.action', 'like', '%_blocked%');
            });
        }

        // Date range filter (d/m/Y format)
        if (! empty($filters['date_from'])) {
            $parsed = Carbon::createFromFormat('d/m/Y', trim((string) $filters['date_from']));
            if ($parsed) {
                $query->where('audit_logs.created_at', '>=', $parsed->copy()->startOfDay());
            }
        }
        if (! empty($filters['date_to'])) {
            $parsed = Carbon::createFromFormat('d/m/Y', trim((string) $filters['date_to']));
            if ($parsed) {
                $query->where('audit_logs.created_at', '<=', $parsed->copy()->endOfDay());
            }
        }

        // Sort
        switch ($filters['sort'] ?? 'newest') {
            case 'oldest':
                $query->orderBy('audit_logs.created_at');
                break;
            case 'newest':
            default:
                $query->orderByDesc('audit_logs.created_at');
                break;
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Distinct action values for the filter dropdown.
     *
     * @return Collection<int, string>
     */
    public static function distinctActions(): Collection
    {
        return AuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');
    }

    /**
     * Distinct table name values for the filter dropdown.
     *
     * @return Collection<int, string>
     */
    public static function distinctTables(): Collection
    {
        return AuditLog::query()
            ->select('table_name')
            ->distinct()
            ->orderBy('table_name')
            ->pluck('table_name');
    }
}
