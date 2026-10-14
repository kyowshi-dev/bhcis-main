@extends('layouts.app')

@section('title', 'Activity Logs')

@section('content')
<div class="space-y-4 lg:space-y-6" x-data="{ showAdvanced: false, openDiffs: {} }">
    <x-page-header
        title="Activity Logs"
        subtitle="Audit trail of all system events - sign-ins, data changes, and administration."
        icon="fa-solid fa-clock-rotate-left"
    />

    {{-- Stats strip --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 lg:gap-4">
        <div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-teal-soft text-primary">
                    <i class="fa-solid fa-chart-line" aria-hidden="true"></i>
                </span>
                <div>
                    <p class="text-xs font-medium" style="color: var(--ink-muted);">Events today</p>
                    <p class="text-2xl font-display font-semibold" style="color: var(--ink);">{{ number_format($stats['events_today']) }}</p>
                </div>
            </div>
        </div>
        <div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg {{ $stats['failures_7d'] > 0 ? 'bg-danger-soft text-danger' : 'bg-teal-soft text-primary' }}">
                    <i class="fa-solid {{ $stats['failures_7d'] > 0 ? 'fa-triangle-exclamation' : 'fa-check' }}" aria-hidden="true"></i>
                </span>
                <div>
                    <p class="text-xs font-medium" style="color: var(--ink-muted);">Failures (7 days)</p>
                    <p class="text-2xl font-display font-semibold" style="color: {{ $stats['failures_7d'] > 0 ? 'var(--danger)' : 'var(--ink)' }};">{{ number_format($stats['failures_7d']) }}</p>
                </div>
            </div>
        </div>
        <div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-primary-soft text-primary">
                    <i class="fa-solid fa-users" aria-hidden="true"></i>
                </span>
                <div>
                    <p class="text-xs font-medium" style="color: var(--ink-muted);">Active users (7 days)</p>
                    <p class="text-2xl font-display font-semibold" style="color: var(--ink);">{{ number_format($stats['active_users_7d']) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Category segmented control --}}
    <div class="flex flex-wrap items-center gap-2">
        @php
            $categoryTabs = [
                '' => ['label' => 'All', 'icon' => 'fa-solid fa-layer-group'],
                \App\Support\AuditTaxonomy::CATEGORY_AUTH => ['label' => 'Sign-ins', 'icon' => 'fa-solid fa-right-to-bracket'],
                \App\Support\AuditTaxonomy::CATEGORY_RECORDS => ['label' => 'Data changes', 'icon' => 'fa-solid fa-database'],
                \App\Support\AuditTaxonomy::CATEGORY_ADMIN => ['label' => 'Administration', 'icon' => 'fa-solid fa-gear'],
                '__failures' => ['label' => 'Failures', 'icon' => 'fa-solid fa-triangle-exclamation'],
            ];

            // Determine the currently active tab key
            $failuresOnly = ! empty($filters['severity']) && $filters['severity'] === \App\Support\AuditTaxonomy::SEVERITY_ERROR;
            $currentTab = '';
            if ($failuresOnly) {
                $currentTab = '__failures';
            } elseif (! empty($filters['category'])) {
                $currentTab = $filters['category'];
            }
        @endphp
        @foreach ($categoryTabs as $key => $tab)
            @php
                $isActive = $currentTab === $key;
                if ($key === '__failures') {
                    $tabFilters = array_diff_key($filters, array_flip(['category', 'severity', 'failures_only']));
                    $tabFilters['severity'] = \App\Support\AuditTaxonomy::SEVERITY_ERROR;
                    $activeClasses = 'bg-danger-soft text-danger border border-danger/30';
                } else {
                    $tabFilters = array_diff_key($filters, array_flip(['severity', 'failures_only']));
                    if ($key !== '') {
                        $tabFilters['category'] = $key;
                    }
                    $activeClasses = 'bg-teal-soft text-primary border border-primary/30';
                }
            @endphp
            <a href="{{ route('activity-logs.index', $tabFilters) }}"
               @if ($isActive) aria-current="true" @endif
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold transition {{ $isActive ? $activeClasses : 'border border-border text-ink-muted hover:bg-black/5' }}">
                <i class="{{ $tab['icon'] }}" aria-hidden="true"></i>
                {{ $tab['label'] }}
            </a>
        @endforeach

        <div class="ml-auto">
            <button type="button" @click="showAdvanced = !showAdvanced"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-border text-xs font-semibold text-ink-muted hover:bg-black/5 transition"
                    :aria-expanded="showAdvanced.toString()">
                <i class="fa-solid fa-sliders" aria-hidden="true"></i>
                Advanced filters
                @if ($activeFilterCount > 0)
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-primary text-white text-[10px] font-bold">{{ $activeFilterCount }}</span>
                @endif
                <i class="fa-solid fa-chevron-down transition-transform" :class="showAdvanced ? 'rotate-180' : ''" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    {{-- Active filter chips --}}
    @php
        $chips = [];
        if (! empty($filters['query'])) {
            $chips[] = ['key' => 'query', 'label' => 'Search: '.$filters['query']];
        }
        if (! empty($filters['user_id'])) {
            $chipUser = $users->firstWhere('id', $filters['user_id']);
            $chips[] = ['key' => 'user_id', 'label' => 'User: '.($chipUser?->username ?? $filters['user_id'])];
        }
        if (! empty($filters['action'])) {
            $chips[] = ['key' => 'action', 'label' => 'Action: '.\App\Support\AuditTaxonomy::classify($filters['action'], $filters['table_name'] ?? '')['label']];
        }
        if (! empty($filters['table_name'])) {
            $chips[] = ['key' => 'table_name', 'label' => 'Table: '.\App\Support\AuditTaxonomy::tableLabel($filters['table_name'])];
        }
        if (! empty($filters['category'])) {
            $chips[] = ['key' => 'category', 'label' => 'Category: '.\App\Support\AuditTaxonomy::categoryLabel($filters['category'])];
        }
        if (! empty($filters['severity'])) {
            $chips[] = ['key' => 'severity', 'label' => 'Severity: '.\App\Support\AuditTaxonomy::severityLabel($filters['severity'])];
        }
        if (! empty($filters['date_from'])) {
            $chips[] = ['key' => 'date_from', 'label' => 'From: '.$filters['date_from']];
        }
        if (! empty($filters['date_to'])) {
            $chips[] = ['key' => 'date_to', 'label' => 'To: '.$filters['date_to']];
        }
        if (! empty($filters['failures_only'])) {
            $chips[] = ['key' => 'failures_only', 'label' => 'Failures only'];
        }
    @endphp
    @if (! empty($chips))
        <div class="flex flex-wrap items-center gap-2">
            @foreach ($chips as $chip)
                <a href="{{ route('activity-logs.index', array_diff_key($filters, [$chip['key'] => true])) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary-soft text-primary text-xs font-medium hover:bg-primary/15 transition">
                    {{ $chip['label'] }}
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </a>
            @endforeach
            <a href="{{ route('activity-logs.index') }}" class="text-xs font-medium hover:underline" style="color: var(--ink-muted);">Clear all</a>
        </div>
    @endif

    {{-- Advanced filter form --}}
    <form method="GET" action="{{ route('activity-logs.index') }}" id="filterForm" x-show="showAdvanced" x-cloak>
        <div class="rounded-xl border border-border bg-surface shadow-sm p-4 lg:p-5 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold" style="color: var(--ink);">Advanced filters</h3>
                <button type="button" onclick="document.getElementById('filterForm').reset(); this.closest('form').submit();"
                        class="text-xs font-medium transition hover:underline" style="color: var(--ink-muted);">
                    Reset
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4">
                <div class="sm:col-span-2">
                    <label for="query" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Search</label>
                    <input type="text" name="query" id="query"
                           value="{{ $filters['query'] ?? '' }}"
                           placeholder="Action, table, user, record ID..."
                           class="w-full rounded-lg border px-3 py-2 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary"
                           style="border-color: var(--border); background: var(--bg-surface); color: var(--ink);">
                </div>

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

                <div>
                    <label for="action" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Action</label>
                    <select name="action" id="action"
                            class="w-full rounded-lg border px-3 py-2 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary"
                            style="border-color: var(--border); background: var(--bg-surface); color: var(--ink);">
                        <option value="">All actions</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action }}" {{ ($filters['action'] ?? '') === $action ? 'selected' : '' }}>
                                {{ \App\Support\AuditTaxonomy::classify($action, $filters['table_name'] ?? '')['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="table_name" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Table</label>
                    <select name="table_name" id="table_name"
                            class="w-full rounded-lg border px-3 py-2 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary"
                            style="border-color: var(--border); background: var(--bg-surface); color: var(--ink);">
                        <option value="">All tables</option>
                        @foreach ($tables as $table)
                            <option value="{{ $table }}" {{ ($filters['table_name'] ?? '') === $table ? 'selected' : '' }}>
                                {{ \App\Support\AuditTaxonomy::tableLabel($table) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="severity" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Severity</label>
                    <select name="severity" id="severity"
                            class="w-full rounded-lg border px-3 py-2 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary"
                            style="border-color: var(--border); background: var(--bg-surface); color: var(--ink);">
                        <option value="">All severities</option>
                        @foreach ($severities as $sev)
                            <option value="{{ $sev }}" {{ ($filters['severity'] ?? '') === $sev ? 'selected' : '' }}>
                                {{ \App\Support\AuditTaxonomy::severityLabel($sev) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="date_from" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Date From</label>
                    <input type="text" name="date_from" id="date_from"
                           value="{{ $filters['date_from'] ?? '' }}"
                           placeholder="DD/MM/YYYY"
                           class="w-full rounded-lg border px-3 py-2 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary"
                           style="border-color: var(--border); background: var(--bg-surface); color: var(--ink);">
                </div>

                <div>
                    <label for="date_to" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Date To</label>
                    <input type="text" name="date_to" id="date_to"
                           value="{{ $filters['date_to'] ?? '' }}"
                           placeholder="DD/MM/YYYY"
                           class="w-full rounded-lg border px-3 py-2 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary"
                           style="border-color: var(--border); background: var(--bg-surface); color: var(--ink);">
                </div>

                <div>
                    <label for="sort" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Sort</label>
                    <select name="sort" id="sort"
                            class="w-full rounded-lg border px-3 py-2 text-sm focus:ring-2 focus:ring-primary/30 focus:border-primary"
                            style="border-color: var(--border); background: var(--bg-surface); color: var(--ink);">
                        <option value="newest" {{ ($filters['sort'] ?? 'newest') === 'newest' ? 'selected' : '' }}>Newest first</option>
                        <option value="oldest" {{ ($filters['sort'] ?? '') === 'oldest' ? 'selected' : '' }}>Oldest first</option>
                    </select>
                </div>
            </div>

            <input type="hidden" name="category" value="{{ $filters['category'] ?? '' }}">

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
                        <th class="px-3 lg:px-6 py-2 lg:py-3 text-left text-xs font-semibold text-ink-muted uppercase tracking-wider whitespace-nowrap">Time</th>
                        <th class="px-3 lg:px-6 py-2 lg:py-3 text-left text-xs font-semibold text-ink-muted uppercase tracking-wider whitespace-nowrap">User</th>
                        <th class="px-3 lg:px-6 py-2 lg:py-3 text-left text-xs font-semibold text-ink-muted uppercase tracking-wider whitespace-nowrap">Event</th>
                        <th class="px-3 lg:px-6 py-2 lg:py-3 text-left text-xs font-semibold text-ink-muted uppercase tracking-wider whitespace-nowrap hidden md:table-cell">Record</th>
                        <th class="px-3 lg:px-6 py-2 lg:py-3 text-left text-xs font-semibold text-ink-muted uppercase tracking-wider whitespace-nowrap hidden lg:table-cell">IP Address</th>
                        <th class="px-3 lg:px-6 py-2 lg:py-3 text-right text-xs font-semibold text-ink-muted uppercase tracking-wider whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-surface divide-y divide-border">
                    @forelse ($logs as $log)
                        @php
                            $recordLink = $log->recordLink();
                            $hasDiff = ! empty($log->old_values) || ! empty($log->new_values);
                        @endphp
                        <tr class="hover:bg-black/5 transition-colors">
                            <td class="px-3 lg:px-6 py-2 lg:py-3 text-sm text-ink-muted whitespace-nowrap">
                                <time datetime="{{ $log->created_at->toIso8601String() }}"
                                      title="{{ $log->created_at->format('M d, Y H:i:s') }}"
                                      class="text-ink-muted">
                                    {{ $log->created_at->diffForHumans(short: true) }}
                                </time>
                            </td>
                            <td class="px-3 lg:px-6 py-2 lg:py-3 text-sm text-ink">
                                {{ $log->username ?? 'System' }}
                            </td>
                            <td class="px-3 lg:px-6 py-2 lg:py-3 text-sm">
                                <x-audit-badge :log="$log" />
                            </td>
                            <td class="px-3 lg:px-6 py-2 lg:py-3 text-sm hidden md:table-cell">
                                @if ($log->table_name)
                                    <span class="text-ink-muted">{{ \App\Support\AuditTaxonomy::tableLabel($log->table_name) }}</span>
                                    @if ($log->record_id)
                                        @if ($recordLink)
                                            <a href="{{ $recordLink }}" class="ml-1.5 text-xs font-medium hover:underline" style="color: var(--primary);">
                                                #{{ $log->record_id }}
                                            </a>
                                        @else
                                            <code class="ml-1.5 text-xs px-1.5 py-0.5 rounded bg-black/[0.04]">#{{ $log->record_id }}</code>
                                        @endif
                                    @endif
                                @else
                                    &mdash;
                                @endif
                            </td>
                            <td class="px-3 lg:px-6 py-2 lg:py-3 text-sm text-ink-muted hidden lg:table-cell font-mono text-xs">
                                {{ $log->ip_address ?? '—' }}
                            </td>
                            <td class="px-3 lg:px-6 py-2 lg:py-3 text-sm text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    @if ($hasDiff)
                                        <button type="button"
                                                @click="openDiffs[{{ $log->id }}] = !openDiffs[{{ $log->id }}]"
                                                :aria-expanded="!!openDiffs[{{ $log->id }}]"
                                                aria-controls="diff-{{ $log->id }}"
                                                aria-label="Toggle field changes for event #{{ $log->id }}"
                                                class="inline-flex items-center px-2 py-1 rounded-full border text-xs font-semibold transition hover:bg-primary/10"
                                                style="border-color: var(--border); color: var(--ink-muted);">
                                            <i class="fa-solid fa-code-compare" aria-hidden="true"></i>
                                        </button>
                                    @endif
                                    <a href="{{ route('activity-logs.show', $log) }}"
                                       class="inline-flex items-center px-2 lg:px-3 py-1 lg:py-1.5 rounded-full border text-xs font-semibold transition hover:bg-primary/10"
                                       style="border-color: var(--primary); color: var(--primary);">
                                        View
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @if ($hasDiff)
                            <tr x-show="openDiffs[{{ $log->id }}]"
                                x-cloak
                                id="diff-{{ $log->id }}"
                                class="border-t border-border/50">
                                <td colspan="6" class="px-3 lg:px-6 py-3 lg:py-4" style="background: var(--bg-page);">
                                    @include('activity-logs.partials._inline-diff', ['log' => $log])
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <x-empty-state
                                    icon="fa-solid fa-clock-rotate-left"
                                    title="No activity logs found"
                                    description="Try adjusting your filters or clearing them to see more events."
                                >
                                    @if ($activeFilterCount > 0)
                                        <a href="{{ route('activity-logs.index') }}"
                                           class="inline-flex items-center justify-center px-4 py-2 rounded-xl text-sm font-semibold text-white shadow-sm transition hover:opacity-90"
                                           style="background: var(--primary);">
                                            Clear filters
                                        </a>
                                    @endif
                                </x-empty-state>
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