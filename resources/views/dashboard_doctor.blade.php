@extends('layouts.app')

@section('title', 'Doctor Dashboard')

@section('content')
<div class="space-y-5 lg:space-y-6">
    <div class="animate-in opacity-0 delay-1 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Doctor Dashboard</h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">Review queued patients and complete consultations efficiently.</p>
        </div>

        @include('dashboard.partials.date-chip')
    </div>

    <x-flash />

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4">
        <x-kpi-card
            label="Consultations today"
            :value="$consultationsToday"
            icon="fa-solid fa-stethoscope"
            tone="primary"
            :href="route('consultations.index')"
            :hrefLabel="'View visits'"
            :animateDelay="2"
        />

        <x-kpi-card
            label="Doctor queue"
            :value="$pendingDoctorCount"
            icon="fa-solid fa-list-check"
            :tone="$pendingDoctorCount > 0 ? 'blue' : 'primary'"
            :href="route('consultations.index')"
            :hrefLabel="$pendingDoctorCount > 0 ? 'Open queue' : null"
            :zeroState="$pendingDoctorCount === 0 ? 'All caught up' : null"
            :animateDelay="3"
        />

        <x-kpi-card
            label="Completed today"
            :value="$completedConsultationsToday"
            icon="fa-solid fa-circle-check"
            tone="primary"
            :href="route('consultations.index')"
            :hrefLabel="'View records'"
            :animateDelay="4"
        />

        <x-kpi-card
            label="Follow-ups today"
            :value="$followUpConsultationsToday"
            icon="fa-solid fa-calendar-check"
            tone="amber"
            :href="route('consultations.index')"
            :hrefLabel="$followUpConsultationsToday > 0 ? 'Review visits' : null"
            :zeroState="$followUpConsultationsToday === 0 ? 'None scheduled' : null"
            :animateDelay="5"
        />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        <div class="lg:col-span-8 animate-in opacity-0 delay-5 rounded-xl border p-4 lg:p-5"
             style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="font-display font-semibold text-lg lg:text-xl" style="color: var(--ink);">Doctor queue</h2>
                    <p class="text-xs mt-1" style="color: var(--ink-muted);">Patients ready for clinical review or already in progress.</p>
                </div>
                <a href="{{ route('consultations.index') }}" class="text-xs font-semibold hover:underline" style="color: var(--primary);">
                    View all
                </a>
            </div>

            <div class="mt-4 divide-y divide-[var(--border)]">
                @forelse ($doctorQueue as $item)
                    @php
                        $isInProgress = str_contains(strtolower((string) $item['status']), 'progress');
                    @endphp
                    <a href="{{ route('consultations.show', $item['id']) }}"
                       class="block py-3 transition-colors hover:bg-black/[0.02] rounded-lg px-2 -mx-2">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold" style="color: var(--ink);">{{ ucwords($item['patient_name']) }}</p>
                                <p class="text-xs mt-1 capitalize flex items-center gap-1.5" style="color: var(--ink-muted);">
                                    <span class="inline-flex h-1.5 w-1.5 rounded-full shrink-0"
                                          style="background: {{ $isInProgress ? 'var(--accent-blue)' : 'var(--primary)' }};"></span>
                                    <span class="truncate">{{ $item['status'] }}@if ($item['complaint_text'] ?? null) · {{ Str::limit($item['complaint_text'], 60) }}@endif</span>
                                </p>
                            </div>
                            <span class="text-xs shrink-0" style="color: var(--ink-subtle);">{{ $item['time'] }}</span>
                        </div>
                    </a>
                @empty
                    <div class="py-10 text-center">
                        <div class="mx-auto w-12 h-12 rounded-full flex items-center justify-center"
                             style="background: var(--teal-soft); color: var(--primary);">
                            <i class="fa-solid fa-list-check text-lg" aria-hidden="true"></i>
                        </div>
                        <p class="mt-3 text-sm font-semibold" style="color: var(--ink);">No patients in the doctor queue</p>
                        <p class="text-xs mt-1" style="color: var(--ink-muted);">Cases appear here after nurse intake validation.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="lg:col-span-4 space-y-4">
            @php $canConsultations = auth()->user()?->hasPermission('consultations') ?? false; @endphp
            @php $canReferrals = auth()->user()?->hasPermission('referrals') ?? false; @endphp
            @if ($canConsultations)
            <div class="animate-in opacity-0 delay-6 rounded-xl border p-4 lg:p-5"
                 style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-display font-semibold text-base" style="color: var(--ink);">Pending referrals</h3>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold"
                          style="background: var(--accent-blue-soft); color: var(--accent-blue);">
                        {{ $pendingReferralsCount }}
                    </span>
                </div>

                @if (empty($pendingReferrals))
                    <div class="py-6 text-center space-y-2">
                        <div style="color: var(--ink-muted);">
                            <svg class="w-10 h-10 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <p class="text-sm font-semibold" style="color: var(--ink);">No pending referrals</p>
                        <p class="text-xs" style="color: var(--ink-muted);">All outward referrals have been completed.</p>
                    </div>
                @else
                    <ul class="space-y-2 max-h-48 overflow-y-auto">
                        @foreach ($pendingReferrals as $ref)
                            <li class="flex items-center justify-between gap-2 py-2 rounded-lg border hover:bg-black/[0.02]"
                                style="border-color: var(--border); background: var(--bg-surface);">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium truncate" style="color: var(--ink);">{{ $ref['patient_name'] }}</p>
                                    <p class="text-xs truncate" style="color: var(--ink-muted);">
                                        @if ($ref['facility'])
                                            {{ $ref['facility'] }} · {{ $ref['referred_at'] }}
                                        @else
                                            {{ $ref['referred_at'] }}
                                        @endif
                                    </p>
                                </div>
                                <a href="{{ route('consultations.show', $ref['consultation_id']) }}"
                                   class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-semibold transition"
                                   style="background: var(--teal-soft); color: var(--primary);">
                                    View <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($canReferrals)
                <a href="{{ route('referrals.index') }}"
                   class="block mt-3 text-center text-xs font-semibold hover:underline"
                   style="color: var(--primary);">
                    View all referrals
                </a>
                @endif
            </div>
            @endif

            <div class="animate-in opacity-0 delay-6 rounded-xl border p-4 lg:p-5"
                 style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
                <h3 class="text-xs font-semibold uppercase tracking-wider" style="color: var(--ink-muted);">Quick actions</h3>
                <div class="mt-3 space-y-2">
                    <a href="{{ route('patients.index', ['queue' => 1]) }}"
                       class="w-full inline-flex items-center justify-center gap-2 px-3 py-2.5 rounded-lg text-xs font-semibold text-white transition-[transform,box-shadow] duration-200 hover:shadow-sm hover:scale-[1.01]"
                       style="background: var(--primary); box-shadow: var(--shadow-sm);">
                        <i class="fa-solid fa-folder-open mr-2" aria-hidden="true"></i>
                        Open patient records
                    </a>
                    @if ($canReferrals)
                    <a href="{{ route('referrals.index') }}"
                       class="w-full inline-flex items-center justify-center gap-2 px-3 py-2.5 rounded-lg text-xs font-semibold border transition hover:bg-black/[0.02]"
                       style="border-color: var(--border); color: var(--primary);">
                        <i class="fa-solid fa-arrow-up-right-from-square mr-2" aria-hidden="true"></i>
                        View referrals
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

@if ($showResultsReady ?? false)
        <x-results-ready-panel
            panelTitle="Recent completed - print handouts"
            panelSubtitle="Today's finalized consultations. Print Rx and diagnosis summaries for patient pickup."
            :showFilters="true"
            filterAction="{{ route('dashboard') }}"
            :resultsReady="$resultsReady"
            :resultsReadyCount="$resultsReadyCount"
            :resultsFilters="$resultsFilters"
        />
    @endif
</div>
@endsection