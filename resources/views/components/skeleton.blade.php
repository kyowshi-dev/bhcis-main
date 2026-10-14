@props([
    'variant' => 'text',
    'width' => null,
    'height' => null,
    'rows' => 1,
    'class' => '',
])

@php
    $isText = $variant === 'text';
    $isCircle = $variant === 'circle';
    $isBlock = $variant === 'block';
    $baseClass = 'block rounded bg-surface-elevated animate-pulse ' . $class;

    $widthClass = $width ? ($width === 'full' ? 'w-full' : $width) : ($isCircle ? 'h-10 w-10' : ($isBlock ? 'w-full h-full' : 'w-3/4'));
    $heightClass = $height && ! $isCircle ? $height : null;
@endphp

@if ($rows > 1)
    <div class="space-y-2" aria-hidden="true">
        @for ($i = 0; $i < $rows; $i++)
            <div {{ $attributes->merge(['class' => $baseClass . ' ' . $widthClass . ($i === $rows - 1 ? ' w-1/2' : '') . ($heightClass ? ' ' . $heightClass : '')]) }}></div>
        @endfor
    </div>
@else
    <div {{ $attributes->merge(['class' => $baseClass . ' ' . $widthClass . ($heightClass ? ' ' . $heightClass : '')]) }} aria-hidden="true"></div>
@endif
