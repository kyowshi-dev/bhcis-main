@php
    $todayLabel = now()->format('F d, Y');
    $weekdayLabel = now()->format('l');
@endphp

<div class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-xs sm:text-sm"
     style="background: var(--bg-surface); border-color: var(--border); color: var(--ink-muted); box-shadow: var(--shadow-sm);">
    <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg"
          style="background: var(--teal-soft); color: var(--primary);">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
            <path d="M16 2v4"></path>
            <path d="M8 2v4"></path>
            <path d="M3 10h18"></path>
        </svg>
    </span>
    <div class="leading-tight">
        <div class="font-semibold" style="color: var(--ink);">{{ $todayLabel }}</div>
        <div class="text-xs" style="color: var(--ink-muted);">{{ $weekdayLabel }}</div>
    </div>
</div>