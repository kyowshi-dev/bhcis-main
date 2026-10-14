@props([
    'log' => null,
    'action' => null,
    'severity' => null,
    'category' => null,
    'label' => null,
    'icon' => null,
    'classes' => null,
])

@php
    if ($log instanceof \App\Models\AuditLog) {
        $spec = $log->badgeSpec();
        $label = $spec['label'];
        $icon = $spec['icon'];
        $classes = $spec['classes'];
    } elseif ($log) {
        // stdClass from DB query (e.g., dashboard)
        $label = $label ?? ucfirst(str_replace('_', ' ', $log->action ?? ''));
        $icon = $icon ?? \App\Support\AuditTaxonomy::actionIcon($log->action ?? '');
        $classes = $classes ?? \App\Support\AuditTaxonomy::badgeClasses($log->severity ?? 'info', $log->category ?? 'records');
    } else {
        $label = $label ?? 'Event';
        $icon = $icon ?? 'fa-solid fa-circle';
        $classes = $classes ?? 'bg-primary-soft text-primary border-primary/30';
    }
@endphp

<span data-audit-badge class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $classes }}">
    <i class="{{ $icon }}" aria-hidden="true"></i>
    <span>{{ $label }}</span>
</span>