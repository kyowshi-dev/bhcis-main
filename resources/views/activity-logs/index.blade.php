@extends('layouts.app')

@section('title', 'Activity Logs')

@section('content')
<div class="space-y-4 lg:space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Activity Logs</h1>
            <p class="text-xs lg:text-sm text-ink-muted mt-1">
                Audit trail of all system events.
            </p>
        </div>
    </div>

    {{-- Filter Form --}}
    <form method="GET" action="{{ route('activity-logs.index') }}" id="filterForm">
        <div class="rounded-xl border border-border bg-surface shadow-sm p-4 lg:p-5 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold" style="color: var(--ink);">Filters</h3>
                <button type="button" onclick="document.getElementById('filterForm').reset(); this.closest('form').submit();"
                        class="text-xs font-medium transition hover:underline" style="color: var(--ink-muted);">
                    Reset
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4">
                {{-- Text Search --}}
                <div class="sm:col-span-2">
                    <label for="query" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Search</label>
                    <input type="text" name="query" id="query"
                           value="{{ $filters['query'] ?? '' }}"
                           placeholder="Action, table, user, record ID..."
                           class="w-full rounded-lg border px-3 py-2 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary"
                           style="border-color: var(--border); background: var(--bg-surface); color: var(--ink);">
                </div>

                {{-- User --}}
                <div>
                    <label for="user_id" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">User</label>
                    <select name="user_id" id="user_id"
                            class="w-full rounded-lg border px-3 py-2 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary"
                            style="border-color: var(--border); background: var(--bg-surface); color: var(--ink);">
                        <option value="">All users</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" {{ ($filters['user_id'] ?? '') == $user->id ? 'selected' : '' }}>
                                {{ $user->username }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Action --}}
                <div>
                    <label for="action" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Action</label>
                    <select name="action" id="action"
                            class="w-full rounded-lg border px-3 py-2 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary"
                            style="border-color: var(--border); background: var(--bg-surface); color: var(--ink);">
                        <option value="">All actions</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action }}" {{ ($filters['action'] ?? '') === $action ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $action)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Table --}}
                <div>
                    <label for="table_name" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Table</label>
                    <select name="table_name" id="table_name"
                            class="w-full rounded-lg border px-3 py-2 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary"
                            style="border-color: var(--border); background: var(--bg-surface); color: var(--ink);">
                        <option value="">All tables</option>
                        @foreach ($tables as $table)
                            <option value="{{ $table }}" {{ ($filters['table_name'] ?? '') === $table ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $table)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Date From --}}
                <div>
                    <label for="date_from" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Date From</label>
                    <input type="text" name="date_from" id="date_from"
                           value="{{ $filters['date_from'] ?? '' }}"
                           placeholder="DD/MM/YYYY"
                           class="w-full rounded-lg border px-3 py-2 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary"
                           style="border-color: var(--border); background: var(--bg-surface); color: var(--ink);">
                </div>

                {{-- Date To --}}
                <div>
                    <label for="date_to" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Date To</label>
                    <input type="text" name="date_to" id="date_to"
                           value="{{ $filters['date_to'] ?? '' }}"
                           placeholder="DD/MM/YYYY"
                           class="w-full rounded-lg border px-3 py-2 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary"
                           style="border-color: var(--border); background: var(--bg-surface); color: var(--ink);">
                </div>

                {{-- Sort --}}
                <div>
                    <label for="sort" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Sort</label>
                    <select name="sort" id="sort"
                            class="w-full rounded-lg border px-3 py-2 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary"
                            style="border-color: var(--border); background: var(--bg-surface); color: var(--ink);">
                        <option value="newest" {{ ($filters['sort'] ?? 'newest') === 'newest' ? 'selected' : '' }}>Newest first</option>
                        <option value="oldest" {{ ($filters['sort'] ?? '') === 'oldest' ? 'selected' : '' }}>Oldest first</option>
                    </select>
                </div>

                {{-- Failure Events Only --}}
                <div class="flex items-end pb-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="failures_only" value="1"
                               {{ (! empty($filters['failures_only'])) ? 'checked' : '' }}
                               class="rounded border-border text-primary focus:ring-primary/30">
                        <span class="text-sm" style="color: var(--ink);">Failures only</span>
                    </label>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-xl text-xs lg:text-sm font-semibold text-white shadow-sm transition hover:opacity-90"
                        style="background: var(--primary);">
                    Apply Filters
                </button>
            </div>
        </div>
    </form>

    {{-- Table --}}
    <div class="overflow-hidden rounded-xl lg:rounded-2xl border border-border bg-surface shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border">
                <thead class="bg-teal-soft">
                    <tr>
                        <th class="px-3 lg:px-6 py-2 lg:py-3 text-left text-xs font-semibold text-ink-muted uppercase tracking-wider whitespace-nowrap">Timestamp</th>
                        <th class="px-3 lg:px-6 py-2 lg:py-3 text-left text-xs font-semibold text-ink-muted uppercase tracking-wider whitespace-nowrap">User</th>
                        <th class="px-3 lg:px-6 py-2 lg:py-3 text-left text-xs font-semibold text-ink-muted uppercase tracking-wider whitespace-nowrap">Action</th>
                        <th class="px-3 lg:px-6 py-2 lg:py-3 text-left text-xs font-semibold text-ink-muted uppercase tracking-wider whitespace-nowrap hidden md:table-cell">Table</th>
                        <th class="px-3 lg:px-6 py-2 lg:py-3 text-left text-xs font-semibold text-ink-muted uppercase tracking-wider whitespace-nowrap hidden lg:table-cell">Record ID</th>
                        <th class="px-3 lg:px-6 py-2 lg:py-3 text-left text-xs font-semibold text-ink-muted uppercase tracking-wider whitespace-nowrap hidden lg:table-cell">IP Address</th>
                        <th class="px-3 lg:px-6 py-2 lg:py-3 text-right text-xs font-semibold text-ink-muted uppercase tracking-wider whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-surface divide-y divide-border">
                    @forelse ($logs as $log)
                        @php
                            $badgeStyle = match($log->action) {
                                'created' => 'background: var(--teal-soft); color: var(--primary);',
                                'updated' => 'background: var(--amber-soft); color: var(--amber);',
                                'deleted' => 'background: var(--danger-soft); color: var(--danger);',
                                default => 'background: var(--primary-soft); color: var(--ink-muted);',
                            };
                        @endphp
                        <tr class="hover:bg-black/5 transition-colors">
                            <td class="px-3 lg:px-6 py-2 lg:py-3 text-sm text-ink-muted whitespace-nowrap">
                                {{ $log->created_at->format('M d, Y H:i') }}
                            </td>
                            <td class="px-3 lg:px-6 py-2 lg:py-3 text-sm text-ink">
                                {{ $log->username ?? 'System' }}
                            </td>
                            <td class="px-3 lg:px-6 py-2 lg:py-3 text-sm">
                                <span class="inline-flex items-center px-2 lg:px-3 py-0.5 lg:py-1 rounded-full text-xs font-semibold" style="{{ $badgeStyle }}">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="px-3 lg:px-6 py-2 lg:py-3 text-sm text-ink-muted hidden md:table-cell">
                                {{ ucfirst(str_replace('_', ' ', $log->table_name)) }}
                            </td>
                            <td class="px-3 lg:px-6 py-2 lg:py-3 text-sm text-ink-muted hidden lg:table-cell">
                                @if ($log->record_id)
                                    <code class="text-xs px-1.5 py-0.5 rounded bg-black/[0.04]">#{{ $log->record_id }}</code>
                                @else
                                    &mdash;
                                @endif
                            </td>
                            <td class="px-3 lg:px-6 py-2 lg:py-3 text-sm text-ink-muted hidden lg:table-cell font-mono text-xs">
                                {{ $log->ip_address ?? '&mdash;' }}
                            </td>
                            <td class="px-3 lg:px-6 py-2 lg:py-3 text-sm text-right">
                                <a href="{{ route('activity-logs.show', $log) }}"
                                   class="inline-flex items-center px-2 lg:px-3 py-1 lg:py-1.5 rounded-full border text-xs font-semibold transition hover:bg-primary/10"
                                   style="border-color: var(--primary); color: var(--primary);">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex justify-center mb-3"><i class="fa-solid fa-clock-rotate-left text-3xl" style="color: var(--ink-subtle);"></i></div>
                                <p class="text-sm font-medium" style="color: var(--ink);">No activity logs found</p>
                                <p class="text-xs mt-1" style="color: var(--ink-muted);">Try adjusting your filters</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        <x-pagination :paginator="$logs" />
    </div>
</div>
@endsection
