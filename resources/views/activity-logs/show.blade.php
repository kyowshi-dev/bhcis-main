@extends('layouts.app')

@section('title', 'Log Detail - Activity Logs')

@section('content')
<div class="space-y-4 lg:space-y-6">
    <div>
        <a href="{{ route('activity-logs.index') }}" class="text-sm font-medium hover:underline mb-1 inline-block" style="color: var(--primary);">Back to Activity Logs</a>
        <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Log Detail</h1>
        <p class="text-sm mt-1" style="color: var(--ink-muted);">Audit log entry #{{ $log->id }}</p>
    </div>

    {{-- Summary Card --}}
    <div class="rounded-xl border p-5 lg:p-6" style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
        <h2 class="font-display font-semibold text-lg mb-4" style="color: var(--ink);">Summary</h2>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <dt class="text-xs font-medium mb-1" style="color: var(--ink-muted);">Timestamp</dt>
                <dd class="text-sm" style="color: var(--ink);">{{ $log->created_at->format('M d, Y H:i:s') }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium mb-1" style="color: var(--ink-muted);">User</dt>
                <dd class="text-sm" style="color: var(--ink);">
                    @if ($log->user)
                        <a href="{{ route('users.edit', $log->user) }}" class="font-medium hover:underline" style="color: var(--primary);">{{ $log->user->username }}</a>
                    @else
                        <span class="text-ink-muted">System</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs font-medium mb-1" style="color: var(--ink-muted);">Action</dt>
                <dd>
                    @php
                        $badgeStyle = match($log->action) {
                            'created' => 'background: var(--teal-soft); color: var(--primary);',
                            'updated' => 'background: var(--amber-soft); color: var(--amber);',
                            'deleted' => 'background: var(--danger-soft); color: var(--danger);',
                            default => 'background: var(--primary-soft); color: var(--ink-muted);',
                        };
                    @endphp
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold" style="{{ $badgeStyle }}">
                        {{ $log->action }}
                    </span>
                </dd>
            </div>
            <div>
                <dt class="text-xs font-medium mb-1" style="color: var(--ink-muted);">Table</dt>
                <dd class="text-sm" style="color: var(--ink);">{{ ucfirst(str_replace('_', ' ', $log->table_name)) }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium mb-1" style="color: var(--ink-muted);">Record ID</dt>
                <dd class="text-sm" style="color: var(--ink);">
                    @if ($log->record_id)
                        <code class="text-xs px-1.5 py-0.5 rounded bg-black/[0.04]">#{{ $log->record_id }}</code>
                    @else
                        &mdash;
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs font-medium mb-1" style="color: var(--ink-muted);">IP Address</dt>
                <dd class="text-sm font-mono" style="color: var(--ink);">{{ $log->ip_address ?? '&mdash;' }}</dd>
            </div>
        </dl>
    </div>

    {{-- Values Diff --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Old Values --}}
        <div class="rounded-xl border p-5 lg:p-6" style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
            <h2 class="font-display font-semibold text-lg mb-3" style="color: var(--ink);">Old Values</h2>
            @if (! empty($log->old_values) && is_array($log->old_values))
                <div class="rounded-lg border overflow-hidden" style="border-color: var(--border);">
                    <table class="min-w-full divide-y divide-border text-sm">
                        <tbody class="divide-y divide-border">
                            @foreach ($log->old_values as $key => $value)
                                @php
                                    $changed = ! isset($log->new_values[$key]) || $log->new_values[$key] !== $value;
                                @endphp
                                <tr class="{{ $changed ? 'bg-danger-soft/30' : '' }}">
                                    <td class="px-3 py-2 font-medium whitespace-nowrap" style="color: var(--ink-muted);">{{ $key }}</td>
                                    <td class="px-3 py-2" style="color: var(--ink);">{{ is_null($value) ? 'null' : (is_array($value) ? json_encode($value) : $value) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-ink-muted italic">No previous values (record was created or has no change history).</p>
            @endif
        </div>

        {{-- New Values --}}
        <div class="rounded-xl border p-5 lg:p-6" style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
            <h2 class="font-display font-semibold text-lg mb-3" style="color: var(--ink);">New Values</h2>
            @if (! empty($log->new_values) && is_array($log->new_values))
                <div class="rounded-lg border overflow-hidden" style="border-color: var(--border);">
                    <table class="min-w-full divide-y divide-border text-sm">
                        <tbody class="divide-y divide-border">
                            @foreach ($log->new_values as $key => $value)
                                @php
                                    $changed = ! isset($log->old_values[$key]) || $log->old_values[$key] !== $value;
                                @endphp
                                <tr class="{{ $changed ? 'bg-teal-soft/40' : '' }}">
                                    <td class="px-3 py-2 font-medium whitespace-nowrap" style="color: var(--ink-muted);">{{ $key }}</td>
                                    <td class="px-3 py-2" style="color: var(--ink);">{{ is_null($value) ? 'null' : (is_array($value) ? json_encode($value) : $value) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-ink-muted italic">No new values (record was deleted or has no change history).</p>
            @endif
        </div>
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
</div>
@endsection
