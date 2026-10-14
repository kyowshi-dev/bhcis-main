<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivityLogIndexRequest;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\ActivityLogQueryService;
use App\Support\AuditTaxonomy;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(ActivityLogIndexRequest $request): View
    {
        $this->authorizePermission('users');

        $filters = $request->validated();
        $perPage = pageSize(auth()->user()->isAdmin() ? 10 : 15);

        $logs = ActivityLogQueryService::paginateIndex($filters, $perPage);
        $stats = ActivityLogQueryService::stats();
        $actions = ActivityLogQueryService::distinctActions();
        $tables = ActivityLogQueryService::distinctTables();
        $users = User::query()->orderBy('username')->get(['id', 'username']);
        $categories = AuditTaxonomy::categories();
        $severities = AuditTaxonomy::severities();

        // Count active filters for the UI indicator
        $activeFilterCount = 0;
        foreach (['query', 'user_id', 'action', 'table_name', 'category', 'severity', 'date_from', 'date_to', 'failures_only', 'sort'] as $key) {
            if (! empty($filters[$key]) && ! ($key === 'sort' && ($filters[$key] ?? 'newest') === 'newest')) {
                $activeFilterCount++;
            }
        }

        return view('activity-logs.index', [
            'logs' => $logs,
            'stats' => $stats,
            'actions' => $actions,
            'tables' => $tables,
            'users' => $users,
            'categories' => $categories,
            'severities' => $severities,
            'filters' => $filters,
            'activeFilterCount' => $activeFilterCount,
        ]);
    }

    public function show(AuditLog $auditLog): View
    {
        $this->authorizePermission('users');

        $auditLog->load('user');

        return view('activity-logs.show', [
            'log' => $auditLog,
        ]);
    }
}
