@props(['label', 'subtitle' => null])

<div>
    <p class="text-[10px] uppercase tracking-wide font-semibold" style="color: var(--ink-muted);">{{ $label }}</p>
    @if ($subtitle)
        <p class="text-xs" style="color: var(--ink-subtle);">{{ $subtitle }}</p>
    @endif
    <div class="mt-0.5 text-sm font-semibold" style="color: var(--ink);">{!! $slot !!}</div>
</div>
