@extends('layouts.public')

@php
    $tabs = [
        'policy' => ['short' => 'Policy', 'full' => 'Privacy Policy', 'icon' => 'fa-solid fa-shield-halved'],
        'purposes' => ['short' => 'Purposes', 'full' => 'Data Processing Purposes', 'icon' => 'fa-solid fa-list-check'],
        'liability' => ['short' => 'Liability', 'full' => 'Liability & Disclaimer', 'icon' => 'fa-solid fa-scale-balanced'],
    ];
    $pageTitle = $tabs[$activeTab]['full'] ?? 'Privacy';
@endphp

@section('title', $pageTitle)

@section('content')
<div class="privacy-page" data-privacy-tabs data-active-tab="{{ $activeTab }}">

    <div class="privacy-header sticky top-0 z-20 mb-4 flex items-start justify-between gap-4 py-3 -mx-4 px-4" style="background: var(--bg-page);">
        <div class="flex min-w-0 items-start gap-3">
            <span class="header-chip mt-0.5" aria-hidden="true">
                <i class="fa-solid fa-shield-halved text-lg"></i>
            </span>
            <div class="min-w-0">
                <h1 class="font-display text-xl font-semibold text-ink sm:text-2xl lg:text-3xl">Privacy &amp; Notices</h1>
                <p class="mt-1 hidden text-sm text-ink-muted sm:block">Barangay Health Center Information System - Sta. Ana</p>
            </div>
        </div>
        <div class="flex shrink-0 items-center gap-1.5">
            <button type="button" id="theme-toggle"
                    class="theme-toggle inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg transition-colors hover:bg-black/[0.03] focus:outline-none focus:ring-2 focus:ring-accent-blue"
                    aria-pressed="false" aria-label="Switch to dark mode" title="Switch to dark mode">
                <i class="fa-solid fa-moon icon-moon text-lg text-ink-muted" aria-hidden="true"></i>
                <i class="fa-solid fa-sun icon-sun text-lg text-ink-muted" aria-hidden="true"></i>
            </button>
            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}"
               class="privacy-back inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90"
               style="background: var(--primary); box-shadow: 0 4px 14px -2px rgba(13, 74, 60, 0.4);">
                <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i>
                Back to System
            </a>
        </div>
    </div>

    <div class="privacy-tabs mb-4 sm:mb-5">
        <div class="flex items-stretch gap-1 rounded-xl border border-border bg-surface p-1 sm:inline-flex" role="tablist" aria-label="Privacy sections">
            @foreach ($tabs as $key => $tab)
                <button type="button"
                        id="tab-{{ $key }}"
                        role="tab"
                        data-tab="{{ $key }}"
                        aria-controls="panel-{{ $key }}"
                        aria-label="{{ $tab['full'] }}"
                        aria-selected="{{ $activeTab === $key ? 'true' : 'false' }}"
                        tabindex="{{ $activeTab === $key ? '0' : '-1' }}"
                        class="inline-flex min-h-11 flex-1 items-center justify-center gap-1.5 whitespace-nowrap rounded-lg px-2.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-accent-blue sm:flex-none sm:px-4 sm:text-sm {{ $activeTab === $key ? 'bg-teal-soft text-primary' : 'text-ink-muted hover:bg-black/[0.03]' }}">
                    <i class="{{ $tab['icon'] }} text-xs" aria-hidden="true"></i>
                    <span>{{ $tab['short'] }}</span>
                </button>
            @endforeach
        </div>
    </div>

    <x-card id="panel-policy"
            role="tabpanel"
            aria-labelledby="tab-policy"
            data-panel="policy"
            tabindex="0"
            :hidden="$activeTab !== 'policy'">
        @include('privacy.partials._policy')
    </x-card>

    <x-card id="panel-purposes"
            role="tabpanel"
            aria-labelledby="tab-purposes"
            data-panel="purposes"
            tabindex="0"
            :hidden="$activeTab !== 'purposes'">
        @include('privacy.partials._purposes')
    </x-card>

    <x-card id="panel-liability"
            role="tabpanel"
            aria-labelledby="tab-liability"
            data-panel="liability"
            tabindex="0"
            :hidden="$activeTab !== 'liability'">
        @include('privacy.partials._liability')
    </x-card>
</div>
@endsection
