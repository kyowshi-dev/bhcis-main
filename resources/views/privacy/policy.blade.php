@extends('layouts.public')

@section('title', 'Privacy Policy')

@section('content')
<div class="flex items-start justify-between gap-4 mb-6 sticky top-0 z-20 py-3 -mx-4 px-4" style="background: var(--bg-page);">
    <div class="flex items-start gap-3">
        <span class="mt-0.5 header-chip">
            <i class="fa-solid fa-shield-halved text-lg" aria-hidden="true"></i>
        </span>
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl text-ink">Privacy Policy</h1>
            <p class="text-sm mt-1 text-ink-muted">Barangay Health Center Information System - Sta. Ana</p>
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
    <div class="prose max-w-none" style="color: var(--ink); font-size: 1rem; line-height: 1.8;">
        @if ($privacy->privacy_policy_content)
            {!! $privacy->privacy_policy_content !!}
        @else
            <p class="text-ink-muted italic">No privacy policy has been configured yet. Please contact the administrator.</p>
        @endif
    </div>

    @if ($privacy->privacy_policy_version)
        <p class="mt-4 text-xs text-ink-muted">Version: {{ $privacy->privacy_policy_version }}</p>
    @endif

    <div class="mt-4 pt-4 border-t border-border">
        <a href="{{ route('privacy.liability') }}" class="text-sm font-medium hover:underline" style="color: var(--primary);">View Liability &amp; Disclaimer &rarr;</a>
    </div>
</x-card>
@endsection
