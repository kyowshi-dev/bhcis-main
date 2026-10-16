@props(['state' => 'due'])

@php
    $states = [
        'due' => ['icon' => 'fa-regular fa-calendar', 'label' => 'Due', 'classes' => 'bg-accent-blue-soft text-accent-blue border-accent-blue/30'],
        'referred' => ['icon' => 'fa-solid fa-arrow-up-right-from-square', 'label' => 'Referred', 'classes' => 'bg-amber-soft text-amber border-amber/30'],
        'overdue' => ['icon' => 'fa-solid fa-circle-exclamation', 'label' => 'Overdue', 'classes' => 'bg-amber-soft text-amber border-amber/30'],
        'out_of_window' => ['icon' => 'fa-solid fa-clock', 'label' => 'Out of window', 'classes' => 'bg-amber-soft text-amber border-amber/30'],
        'no_show' => ['icon' => 'fa-solid fa-user-clock', 'label' => 'No-show', 'classes' => 'bg-amber-soft text-amber border-amber/30'],
        'complete' => ['icon' => 'fa-solid fa-circle-check', 'label' => 'Complete', 'classes' => null],
        'waiting' => ['icon' => 'fa-solid fa-circle', 'label' => 'Waiting', 'classes' => null],
    ];
    $s = $states[$state] ?? $states['due'];
    // $slot is a ComponentSlot object (always truthy) - compare its text.
    $slotText = trim((string) $slot);
    $label = $slotText !== '' ? $slotText : $s['label'];
@endphp

@if ($s['classes'] === null)
    <span class="inline-flex items-center gap-1.5 whitespace-nowrap text-xs font-medium text-ink-muted">
        <i class="{{ $s['icon'] }}" aria-hidden="true"></i>
        <span>{{ $label }}</span>
    </span>
@else
    <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $s['classes'] }}">
        <i class="{{ $s['icon'] }}" aria-hidden="true"></i>
        <span>{{ $label }}</span>
    </span>
@endif
