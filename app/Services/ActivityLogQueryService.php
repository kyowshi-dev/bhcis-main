<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Support\AuditTaxonomy;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class ActivityLogQueryService
{
    /**
     * Paginate audit log entries with filtering.
     *
     * @param  array{
     *     query?: string,
     *     user_id?: int|string,
     *     action?: string,
     *     table_name?: string,
     *     category?: string,
     *     severity?: string,
     *     date_from?: string,
     *     date_to?: string,
     *     failures_only?: bool,
     *     sort?: string,
     *     per_page?: int
     * }  $filters
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

        // Filter by category
        if (! empty($filters['category'])) {
            $query->where('audit_logs.category', (string) $filters['category']);
        }

        // Filter by severity
        if (! empty($filters['severity'])) {
            $query->where('audit_logs.severity', (string) $filters['severity']);
        }

        // Failure-only filter (back-compat): maps to severity=error
        if (! empty($filters['failures_only'])) {
            $query->where('audit_logs.severity', AuditTaxonomy::SEVERITY_ERROR);
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

        // Use validated per_page if provided, otherwise fall back to default
        $perPage = ! empty($filters['per_page'])
            ? (int) $filters['per_page']
            : $perPage;

        // Clamp to allowed options
        $allowed = pageSizeOptions();
        if (! in_array($perPage, $allowed, true)) {
            $perPage = 15;
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Get summary stats for the filter strip.
     *
     * @return array{events_today: int, failures_7d: int, active_users_7d: int}
     */
    public static function stats(): array
    {
        $todayStart = now()->startOfDay();
        $sevenDaysAgo = now()->subDays(7);

        $eventsToday = AuditLog::query()
            ->where('created_at', '>=', $todayStart)
            ->count();

        $failures7d = AuditLog::query()
            ->where('created_at', '>=', $sevenDaysAgo)
            ->where('severity', AuditTaxonomy::SEVERITY_ERROR)
            ->count();

        $activeUsers7d = AuditLog::query()
            ->where('created_at', '>=', $sevenDaysAgo)
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');

        return [
            'events_today' => $eventsToday,
            'failures_7d' => $failures7d,
            'active_users_7d' => $activeUsers7d,
        ];
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

    /**
     * Distinct category values.
     *
     * @return Collection<int, string>
     */
    public static function distinctCategories(): Collection
    {
        return AuditLog::query()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
    }

    /**
     * Distinct severity values.
     *
     * @return Collection<int, string>
     */
    public static function distinctSeverities(): Collection
    {
        return AuditLog::query()
            ->select('severity')
            ->distinct()
            ->orderBy('severity')
            ->pluck('severity');
    }
}
