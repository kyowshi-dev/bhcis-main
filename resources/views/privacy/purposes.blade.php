@extends('layouts.public')

@section('title', 'Data Processing Purposes')

@section('content')
<div class="flex items-start justify-between gap-4 mb-6 sticky top-0 z-20 py-3 -mx-4 px-4" style="background: var(--bg-page);">
    <div class="flex items-start gap-3">
        <span class="mt-0.5 header-chip">
            <i class="fa-solid fa-list-check text-lg" aria-hidden="true"></i>
        </span>
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl text-ink">Data Processing Purposes</h1>
            <p class="text-sm mt-1 text-ink-muted">How your data is collected and used</p>
        </div>
    </div>
    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}"
       class="shrink-0 inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white transition hover:opacity-90"
       style="background: var(--primary); box-shadow: 0 4px 14px -2px rgba(13, 74, 60, 0.4);">
        <i class="fa-solid fa-arrow-left text-xs"></i>
        Back to System
    </a>
</div>

<x-card class="mt-6">
    @php
        $purposes = $privacy->purpose_limitation ?? [];
    @endphp

    @if (!empty($purposes))
        <ul class="space-y-3" style="font-size: 1rem; line-height: 1.8;">
            @foreach ($purposes as $purpose)
                <li class="flex items-start gap-3">
                    <i class="fa-solid fa-check-circle mt-0.5" style="color: var(--primary);"></i>
                    <span style="color: var(--ink);">{{ $purpose }}</span>
                </li>
            @endforeach
        </ul>
    @else
        <p class="text-ink-muted italic">No data processing purposes have been configured yet.</p>
    @endif
</x-card>
@endsection
