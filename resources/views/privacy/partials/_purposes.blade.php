@php
    $purposes = $privacy->purpose_limitation ?? [];
@endphp

@if (!empty($purposes))
    <ul class="space-y-2.5 text-base leading-relaxed text-ink">
        @foreach ($purposes as $purpose)
            <li class="flex items-start gap-2.5">
                <i class="fa-solid fa-circle-check mt-1 text-primary" aria-hidden="true"></i>
                <span>{{ $purpose }}</span>
            </li>
        @endforeach
    </ul>
@else
    <x-empty-state
        icon="fa-solid fa-list-check"
        title="No data processing purposes yet"
        description="No data processing purposes have been configured yet. Please contact the administrator."
    />
@endif
