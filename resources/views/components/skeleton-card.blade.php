@props([
    'lines' => 2,
    'showAvatar' => false,
    'class' => '',
])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-border bg-surface p-4 ' . $class]) }} aria-hidden="true">
    @if ($showAvatar)
        <div class="flex items-center gap-3 mb-3">
            <div class="h-10 w-10 rounded-full bg-surface-elevated animate-pulse shrink-0"></div>
            <div class="flex-1 min-w-0">
                <div class="h-3.5 w-3/4 rounded bg-surface-elevated animate-pulse"></div>
                <div class="mt-1.5 h-3 w-1/2 rounded bg-surface-elevated animate-pulse"></div>
            </div>
        </div>
    @endif

    @if ($lines > 0)
        <div class="space-y-2">
            @for ($i = 0; $i < $lines; $i++)
                <div class="h-3 rounded bg-surface-elevated animate-pulse {{ $i === $lines - 1 ? 'w-1/2' : 'w-full' }}"></div>
            @endfor
        </div>
    @endif

    {{ $slot }}
</div>
