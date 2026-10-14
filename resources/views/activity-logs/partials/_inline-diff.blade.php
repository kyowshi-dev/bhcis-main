@php
    $old = is_array($log->old_values) ? $log->old_values : [];
    $new = is_array($log->new_values) ? $log->new_values : [];
    $keys = array_unique(array_merge(array_keys($old), array_keys($new)));
    $action = $log->action;
@endphp

@if (empty($keys))
    <p class="text-xs text-ink-muted italic">No field-level data recorded for this event.</p>
@else
    <div class="rounded-lg border overflow-hidden" style="border-color: var(--border); background: var(--bg-surface);">
        <table class="min-w-full divide-y divide-border text-xs">
            <thead class="bg-teal-soft">
                <tr>
                    <th class="px-3 py-1.5 text-left font-semibold text-ink-muted uppercase tracking-wider">Field</th>
                    <th class="px-3 py-1.5 text-left font-semibold text-ink-muted uppercase tracking-wider">
                        {{ $action === 'created' ? 'Value' : 'Old' }}
                    </th>
                    <th class="px-3 py-1.5 text-left font-semibold text-ink-muted uppercase tracking-wider">
                        {{ $action === 'deleted' ? 'Removed value' : 'New' }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @foreach ($keys as $key)
                    @php
                        $hasOld = array_key_exists($key, $old);
                        $hasNew = array_key_exists($key, $new);
                        $changed = $hasOld && $hasNew && $old[$key] !== $new[$key];
                        $format = fn ($v) => is_null($v) ? 'null' : (is_bool($v) ? ($v ? 'true' : 'false') : (is_array($v) ? json_encode($v) : (string) $v));
                    @endphp
                    <tr>
                        <td class="px-3 py-1.5 font-medium whitespace-nowrap text-ink-muted">{{ $key }}</td>
                        <td class="px-3 py-1.5 {{ $hasOld && ($changed || !$hasNew) ? 'bg-danger-soft/30 text-danger' : 'text-ink' }}">
                            @if ($hasOld)
                                {{ $format($old[$key]) }}
                            @elseif ($action === 'created')
                                <span class="text-ink-subtle">&mdash;</span>
                            @else
                                <span class="text-ink-subtle">&mdash;</span>
                            @endif
                        </td>
                        <td class="px-3 py-1.5 {{ $hasNew && ($changed || !$hasOld) ? 'bg-teal-soft/40 text-primary' : 'text-ink' }}">
                            @if ($hasNew)
                                {{ $format($new[$key]) }}
                            @else
                                <span class="text-ink-subtle">&mdash;</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-2 flex justify-end">
        <a href="{{ route('activity-logs.show', $log) }}"
           class="text-xs font-medium hover:underline" style="color: var(--primary);">
            Open full detail
            <i class="fa-solid fa-arrow-right ml-1" aria-hidden="true"></i>
        </a>
    </div>
@endif