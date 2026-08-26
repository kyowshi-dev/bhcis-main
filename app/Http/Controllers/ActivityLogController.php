<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivityLogIndexRequest;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\ActivityLogQueryService;
use Illuminate\Contracts\View\View;

class ActivityLogController extends Controller
{
    public function index(ActivityLogIndexRequest $request): View
    {
        $this->authorizePermission('users');

        $filters = $request->validated();
        $perPage = pageSize(auth()->user()->isAdmin() ? 10 : 15);

        $logs = ActivityLogQueryService::paginateIndex($filters, $perPage);
        $actions = ActivityLogQueryService::distinctActions();
        $tables = ActivityLogQueryService::distinctTables();
        $users = User::query()->orderBy('username')->get(['id', 'username']);

        return view('activity-logs.index', [
            'logs' => $logs,
            'actions' => $actions,
            'tables' => $tables,
            'users' => $users,
            'filters' => $filters,
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
