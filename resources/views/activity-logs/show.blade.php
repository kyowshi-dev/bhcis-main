@extends('layouts.app')

@section('title', 'Log Detail - Activity Logs')

@section('content')
<div class="space-y-4 lg:space-y-6" x-data="{ showUnchanged: false, copied: null }">
    <div>
        <a href="{{ route('activity-logs.index') }}" class="text-sm font-medium hover:underline mb-1 inline-flex items-center gap-1.5" style="color: var(--primary);">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            Back to Activity Logs
        </a>
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mt-1">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <x-audit-badge :log="$log" />
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border border-border text-ink-muted">
                        {{ \App\Support\AuditTaxonomy::categoryLabel($log->category) }}
                    </span>
                </div>
                <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">{{ $log->description }}</h1>
                <p class="text-sm mt-1" style="color: var(--ink-muted);">
                    Event #{{ $log->id }} &middot;
                    <time datetime="{{ $log->created_at->toIso8601String() }}" title="{{ $log->created_at->format('M d, Y H:i:s') }}">
                        {{ $log->created_at->format('M d, Y H:i:s') }}
                    </time>
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if ($link = $log->recordLink())
                    <a href="{{ $link }}"
                       class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl text-sm font-semibold text-white shadow-md transition hover:opacity-90"
                       style="background: var(--primary);">
                        <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                        Open record
                    </a>
                @endif
                <button type="button"
                        data-copy="{{ $log->description.' - '.$log->created_at->format('M d, Y H:i:s') }}"
                        @click="
                            navigator.clipboard.writeText($el.dataset.copy);
                            copied = 'summary';
                            setTimeout(() => copied = null, 2000);
                        "
                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl border text-sm font-semibold transition hover:bg-primary/10"
                        style="border-color: var(--primary); color: var(--primary);">
                    <i class="fa-solid" :class="copied === 'summary' ? 'fa-check' : 'fa-copy'" aria-hidden="true"></i>
                    <span x-text="copied === 'summary' ? 'Copied' : 'Copy summary'">Copy summary</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Summary Card --}}
    <div class="rounded-xl border p-5 lg:p-6" style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
        <h2 class="font-display font-semibold text-lg mb-4" style="color: var(--ink);">Summary</h2>
        <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <dt class="text-xs font-medium mb-1" style="color: var(--ink-muted);">Timestamp</dt>
                <dd class="text-sm" style="color: var(--ink);">{{ $log->created_at->format('M d, Y H:i:s') }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium mb-1" style="color: var(--ink-muted);">User</dt>
                <dd class="text-sm" style="color: var(--ink);">
                    @if ($log->user)
                        @if (auth()->user()->hasPermission('users'))
                            <a href="{{ route('users.edit', $log->user) }}" class="font-medium hover:underline" style="color: var(--primary);">{{ $log->user->username }}</a>
                        @else
                            <span class="font-medium">{{ $log->user->username }}</span>
                        @endif
                    @else
                        <span class="text-ink-muted">System</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs font-medium mb-1" style="color: var(--ink-muted);">Severity</dt>
                <dd class="text-sm" style="color: var(--ink);">{{ \App\Support\AuditTaxonomy::severityLabel($log->severity) }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium mb-1" style="color: var(--ink-muted);">Table</dt>
                <dd class="text-sm" style="color: var(--ink);">{{ \App\Support\AuditTaxonomy::tableLabel($log->table_name) }} <span class="text-ink-subtle text-xs">({{ $log->table_name }})</span></dd>
            </div>
            <div>
                <dt class="text-xs font-medium mb-1" style="color: var(--ink-muted);">Record</dt>
                <dd class="text-sm" style="color: var(--ink);">
                    @if ($log->record_id)
                        @if ($link)
                            <a href="{{ $link }}" class="font-medium hover:underline" style="color: var(--primary);">#{{ $log->record_id }}</a>
                        @else
                            <code class="text-xs px-1.5 py-0.5 rounded bg-black/[0.04]">#{{ $log->record_id }}</code>
                        @endif
                    @else
                        &mdash;
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs font-medium mb-1" style="color: var(--ink-muted);">IP Address</dt>
                <dd class="text-sm font-mono" style="color: var(--ink);">{{ $log->ip_address ?? '—' }}</dd>
            </div>
        </dl>
    </div>

    {{-- Values Diff --}}
    @php
        $old = is_array($log->old_values) ? $log->old_values : [];
        $new = is_array($log->new_values) ? $log->new_values : [];
        $allKeys = array_unique(array_merge(array_keys($old), array_keys($new)));
        $changedKeys = array_values(array_filter($allKeys, fn ($k) => ! array_key_exists($k, $old) || ! array_key_exists($k, $new) || $old[$k] !== $new[$k]));
        $unchangedKeys = array_values(array_diff($allKeys, $changedKeys));
        $visibleKeys = $changedKeys;
        if (count($changedKeys) === 0 && count($unchangedKeys) > 0) {
            // No changed-field semantics (e.g. create/delete payloads): show all
            $visibleKeys = $allKeys;
            $unchangedKeys = [];
        }
        $formatValue = fn ($v) => is_null($v) ? 'null' : (is_bool($v) ? ($v ? 'true' : 'false') : (is_array($v) ? json_encode($v) : (string) $v));
        $isCreate = $log->action === 'created';
        $isDelete = $log->action === 'deleted';
    @endphp

    @if (empty($allKeys))
        <div class="rounded-xl border p-5 lg:p-6" style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
            <p class="text-sm text-ink-muted italic">No field-level data recorded for this event.</p>
        </div>
    @else
        <div class="rounded-xl border p-5 lg:p-6" style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <h2 class="font-display font-semibold text-lg" style="color: var(--ink);">
                    {{ $isCreate ? 'Recorded values' : ($isDelete ? 'Removed values' : 'Field changes') }}
                </h2>
                <div class="flex items-center gap-2">
                    @if (! empty($changedKeys))
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-teal-soft text-primary">
                            {{ count($changedKeys) }} changed
                        </span>
                    @endif
                    <button type="button"
                            data-copy="{{ json_encode(['old' => $log->old_values, 'new' => $log->new_values], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}"
                            @click="
                                navigator.clipboard.writeText($el.dataset.copy);
                                copied = 'json';
                                setTimeout(() => copied = null, 2000);
                            "
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border text-xs font-semibold transition hover:bg-primary/10"
                            style="border-color: var(--primary); color: var(--primary);">
                        <i class="fa-solid" :class="copied === 'json' ? 'fa-check' : 'fa-copy'" aria-hidden="true"></i>
                        <span x-text="copied === 'json' ? 'Copied' : 'Copy JSON'">Copy JSON</span>
                    </button>
                </div>
            </div>

            <div class="rounded-lg border overflow-hidden" style="border-color: var(--border);">
                <table class="min-w-full divide-y divide-border text-sm">
                    <thead class="bg-teal-soft">
                        <tr>
                            <th class="px-3 lg:px-4 py-2 text-left text-xs font-semibold text-ink-muted uppercase tracking-wider">Field</th>
                            <th class="px-3 lg:px-4 py-2 text-left text-xs font-semibold text-ink-muted uppercase tracking-wider">
                                {{ $isCreate ? 'Value' : 'Old value' }}
                            </th>
                            <th class="px-3 lg:px-4 py-2 text-left text-xs font-semibold text-ink-muted uppercase tracking-wider">
                                {{ $isDelete ? 'Removed value' : 'New value' }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach ($visibleKeys as $key)
                            @php
                                $hasOld = array_key_exists($key, $old);
                                $hasNew = array_key_exists($key, $new);
                                $isChanged = $hasOld && $hasNew && $old[$key] !== $new[$key];
                            @endphp
                            <tr>
                                <td class="px-3 lg:px-4 py-2 font-medium whitespace-nowrap text-ink-muted">{{ $key }}</td>
                                <td class="px-3 lg:px-4 py-2 {{ $hasOld && $isChanged ? 'bg-danger-soft/30' : '' }}" style="color: {{ $hasOld && $isChanged ? 'var(--danger)' : 'var(--ink)' }};">
                                    @if ($hasOld)
                                        {{ $formatValue($old[$key]) }}
                                    @else
                                        <span class="text-ink-subtle">&mdash;</span>
                                    @endif
                                </td>
                                <td class="px-3 lg:px-4 py-2 {{ $hasNew && $isChanged ? 'bg-teal-soft/40' : '' }}" style="color: {{ $hasNew && $isChanged ? 'var(--primary)' : 'var(--ink)' }};">
                                    @if ($hasNew)
                                        {{ $formatValue($new[$key]) }}
                                    @else
                                        <span class="text-ink-subtle">&mdash;</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach

                        {{-- Unchanged fields, collapsed --}}
                        @if (! empty($unchangedKeys))
                            <tr x-show="showUnchanged" x-cloak>
                                <td colspan="3" class="px-3 lg:px-4 py-2" style="background: var(--bg-page);">
                                    <span class="text-xs font-semibold" style="color: var(--ink-subtle);">Unchanged fields</span>
                                </td>
                            </tr>
                            @foreach ($unchangedKeys as $key)
                                <tr x-show="showUnchanged" x-cloak>
                                    <td class="px-3 lg:px-4 py-2 font-medium whitespace-nowrap text-ink-subtle">{{ $key }}</td>
                                    <td class="px-3 lg:px-4 py-2 text-ink-subtle">{{ $formatValue($old[$key]) }}</td>
                                    <td class="px-3 lg:px-4 py-2 text-ink-subtle">{{ $formatValue($new[$key]) }}</td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

            @if (! empty($unchangedKeys))
                <button type="button"
                        @click="showUnchanged = !showUnchanged"
                        :aria-expanded="showUnchanged.toString()"
                        class="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold hover:underline"
                        style="color: var(--primary);">
                    <i class="fa-solid fa-chevron-down transition-transform" :class="showUnchanged ? 'rotate-180' : ''" aria-hidden="true"></i>
                    <span x-text="(showUnchanged ? 'Hide' : 'Show') + ' {{ count($unchangedKeys) }} unchanged field{{ count($unchangedKeys) === 1 ? '' : 's' }}'">Show unchanged fields</span>
                </button>
            @endif
        </div>

        {{-- Raw JSON Toggle --}}
        <div class="rounded-xl border p-5 lg:p-6" style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
            <details>
                <summary class="text-sm font-semibold cursor-pointer select-none" style="color: var(--ink);">Raw JSON</summary>
                <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div>
                        <h3 class="text-xs font-medium mb-2" style="color: var(--ink-muted);">Old Values</h3>
                        <pre class="text-xs p-3 rounded-lg overflow-x-auto" style="background: var(--bg-muted); color: var(--ink);">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                    <div>
                        <h3 class="text-xs font-medium mb-2" style="color: var(--ink-muted);">New Values</h3>
                        <pre class="text-xs p-3 rounded-lg overflow-x-auto" style="background: var(--bg-muted); color: var(--ink);">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>
            </details>
        </div>
    @endif
</div>
@endsection