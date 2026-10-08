@props([
    'label' => '',
    'value' => 0,
    'icon' => 'fa-solid fa-chart-bar',
    'tone' => 'primary',
    'href' => null,
    'hrefLabel' => null,
    'zeroState' => null,
    'animateDelay' => 1,
])

@php
    $toneStyles = match($tone) {
        'primary' => ['border' => 'var(--primary)', 'iconBg' => 'var(--teal-soft)', 'iconColor' => 'var(--primary)', 'valueColor' => '', 'linkColor' => 'var(--primary)'],
        'blue'    => ['border' => 'var(--accent-blue)', 'iconBg' => 'var(--accent-blue-soft)', 'iconColor' => 'var(--accent-blue)', 'valueColor' => 'var(--accent-blue)', 'linkColor' => 'var(--accent-blue)'],
        'amber'   => ['border' => 'var(--amber)', 'iconBg' => 'var(--amber-soft)', 'iconColor' => 'var(--amber)', 'valueColor' => 'var(--amber)', 'linkColor' => 'var(--amber)'],
        'danger'  => ['border' => 'var(--danger)', 'iconBg' => 'var(--danger-soft)', 'iconColor' => 'var(--danger)', 'valueColor' => 'var(--danger)', 'linkColor' => 'var(--danger)'],
        'teal'    => ['border' => 'var(--primary)', 'iconBg' => 'var(--teal-soft)', 'iconColor' => 'var(--primary)', 'valueColor' => '', 'linkColor' => 'var(--primary)'],
        default   => ['border' => 'var(--primary)', 'iconBg' => 'var(--teal-soft)', 'iconColor' => 'var(--primary)', 'valueColor' => '', 'linkColor' => 'var(--primary)'],
    };

    $isZero = (int) $value === 0;
    $valueColor = $isZero ? '' : $toneStyles['valueColor'];
    $labelColor = $isZero ? 'var(--ink-muted)' : $toneStyles['valueColor'];
    $borderLeft = $toneStyles['border'];
    $iconBg = $toneStyles['iconBg'];
    $iconColor = $toneStyles['iconColor'];

    $hasHref = (bool) $href;
    $tag = $hasHref ? 'a' : 'div';
    $baseClasses = 'kpi-card flex items-center gap-3 p-4 rounded-xl border transition-[transform,box-shadow] duration-200 hover:scale-[1.01] hover:shadow-md animate-in opacity-0 delay-' . $animateDelay;
    $style = "background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid {$borderLeft};";

    $extraAttributes = [];
    if ($hasHref) {
        $extraAttributes['href'] = $href;
        $extraAttributes['aria-label'] = $extraAttributes['aria-label'] ?? ($label . ', ' . $value);
    }
@endphp

<{{ $tag }} {{ $attributes->merge(['class' => $baseClasses, 'style' => $style])->merge($extraAttributes) }}>
    <span class="kpi-card__icon" style="background: {{ $iconBg }}; color: {{ $iconColor }};">
        <i class="{{ $icon }}" aria-hidden="true"></i>
    </span>
    <div class="min-w-0 flex-1">
        <p class="kpi-card__label truncate" style="color: {{ $labelColor }};">{{ $label }}</p>
        @if ($isZero)
            <p class="kpi-card__value">0</p>
            @if ($hasHref && $hrefLabel)
                <p class="text-[10px] font-bold truncate block mt-0.5" style="color: {{ $toneStyles['linkColor'] }};">{{ $hrefLabel }}</p>
            @elseif ($zeroState)
                <p class="text-[10px] truncate mt-0.5" style="color: var(--ink-muted);">{{ $zeroState }}</p>
            @endif
        @else
            <p class="kpi-card__value" style="color: {{ $valueColor }};">{{ $value }}</p>
            @if ($hasHref && $hrefLabel)
                <p class="text-[10px] font-bold truncate block mt-0.5" style="color: {{ $toneStyles['linkColor'] }};">{{ $hrefLabel }}</p>
            @endif
        @endif
    </div>
</{{ $tag }}>