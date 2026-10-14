@props([
    'columns' => 5,
    'count' => 5,
])

@php
    $widths = ['w-20', 'w-40', 'w-24', 'w-28', 'w-24', 'w-32', 'w-20'];
@endphp

<div aria-hidden="true">
    @for ($row = 0; $row < $count; $row++)
        <div class="flex items-center gap-4 px-4 py-4 border-b border-border last:border-0">
            @for ($col = 0; $col < $columns; $col++)
                <div class="flex-1 min-w-0">
                    <div class="h-3.5 rounded bg-surface-elevated animate-pulse {{ $widths[$col % count($widths)] }}"></div>
                    @if ($col === 1)
                        <div class="mt-1.5 h-3 rounded bg-surface-elevated animate-pulse w-24"></div>
                    @endif
                </div>
            @endfor
        </div>
    @endfor
</div>
