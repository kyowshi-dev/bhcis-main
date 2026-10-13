@props([
    'url' => '#',
    'label' => '',
    'icon' => '',
    'iconSize' => 'text-sm opacity-70',
    'permission' => true,
    'swalError' => null,
    'active' => false,
    'ariaLabel' => null,
])

@php($disabled = ! $permission)

<a href="{{ $url }}"
   aria-current="{{ $active ? 'page' : 'false' }}"
   aria-label="{{ $ariaLabel ?? $label }}"
   class="nav-link flex items-center gap-2.5 px-2.5 py-2.5 lg:py-2 min-h-[44px] lg:min-h-0 rounded-lg text-[13px] font-medium transition-colors duration-200 text-ink-muted hover:bg-black/5 {{ $disabled ? 'disabled' : '' }}"
   {!! $disabled && $swalError ? 'onclick="'.$swalError.'"' : '' !!}>
    <i class="{{ $icon }} {{ $iconSize }} shrink-0" aria-hidden="true"></i>
    <span>{{ $label }}</span>
</a>
