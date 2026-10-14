@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-5 lg:space-y-6">
    <div class="animate-in opacity-0 delay-1 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">
                Welcome back, <span style="color: var(--primary);">{{ auth()->user()?->name ?? 'Admin' }}</span>
            </h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">
            Ania ang kinatibuk-ang impormasyon bahin sa imong health center.
            </p>
        </div>

        @include('dashboard.partials.date-chip')
    </div>

    <x-flash />

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4">
        <x-kpi-card
            label="Total Patients"
            :value="$totalPatients"
            icon="fa-solid fa-users"
            tone="primary"
            :href="route('patients.create')"
            :hrefLabel="$totalPatients === 0 ? 'Register first patient' : 'Register patient'"
            :zeroState="$totalPatients === 0 ? 'Register first patient' : null"
            :animateDelay="2"
        />

        <x-kpi-card
            label="Pending check-ups"
            :value="$pendingAppointments"
            icon="fa-solid fa-circle-check"
            tone="primary"
            :href="route('consultations.index')"
            :hrefLabel="$pendingAppointments === 0 ? null : 'Manage queue'"
            :zeroState="$pendingAppointments === 0 ? 'All caught up' : null"
            :animateDelay="3"
        />

        <x-kpi-card
            label="Overdue Immunizations"
            :value="$overdueImmunizations"
            icon="fa-solid fa-circle-exclamation"
            :tone="$overdueImmunizations > 0 ? 'danger' : 'primary'"
            :href="route('immunizations.index')"
            :hrefLabel="$overdueImmunizations === 0 ? null : 'View immunizations'"
            :zeroState="$overdueImmunizations === 0 ? 'On track' : null"
            :animateDelay="4"
        />

        <x-kpi-card
            label="Follow-up today"
            :value="$followUpConsultationsToday"
            icon="fa-solid fa-calendar-check"
            tone="amber"
            :href="route('consultations.index')"
            :hrefLabel="$followUpConsultationsToday === 0 ? null : 'Review visits'"
            :zeroState="$followUpConsultationsToday === 0 ? 'None scheduled' : null"
            :animateDelay="5"
        />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 lg:gap-4">
        <div class="lg:col-span-2 dashboard-chart animate-in opacity-0 delay-6 rounded-xl border p-3 lg:p-4"
             style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <h2 class="font-display font-semibold text-base lg:text-lg" style="color: var(--ink);">Patient Volume</h2>
                    <p class="text-[11px] mt-0.5 truncate" style="color: var(--ink-muted);">Visits over the last 7 days</p>
                </div>
                <span class="hidden sm:inline-flex shrink-0 items-center px-2 py-0.5 rounded-full text-[10px] font-semibold"
                      style="background: var(--teal-soft); color: var(--primary);">
                    Weekly trend
                </span>
            </div>

            <div class="dashboard-chart__plot mt-2">
                <livewire:livewire-line-chart
                    key="{{ $patientVolumeChartModel->reactiveKey() }}"
                    :line-chart-model="$patientVolumeChartModel"
                />
            </div>
        </div>

        <div class="dashboard-chart animate-in opacity-0 delay-7 rounded-xl border p-3 lg:p-4"
             style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <h2 class="font-display font-semibold text-base lg:text-lg" style="color: var(--ink);">Top Illnesses</h2>
                    <p class="text-[11px] mt-0.5 truncate" style="color: var(--ink-muted);">Last 30 days</p>
                </div>
                <span class="hidden sm:inline-flex shrink-0 items-center px-2 py-0.5 rounded-full text-[10px] font-semibold"
                      style="background: var(--teal-soft); color: var(--primary);">
                    Diagnoses
                </span>
            </div>

            <div class="dashboard-chart__plot mt-2">
                <livewire:livewire-pie-chart
                    key="{{ $presentingIllnessesChartModel->reactiveKey() }}"
                    :pie-chart-model="$presentingIllnessesChartModel"
                />
            </div>

            <div class="mt-2 space-y-1.5 max-h-36 overflow-y-auto">
                @forelse($topPresentingIllnesses as $illness)
                    <div class="flex items-center justify-between gap-2 rounded-lg border px-2 py-1.5"
                         style="border-color: var(--border); background: var(--bg-surface);">
                        <div class="min-w-0">
                            <p class="font-semibold text-xs truncate" style="color: var(--ink);">{{ $illness->name }}</p>
                        </div>
                        <span class="inline-flex shrink-0 items-center justify-center rounded-full px-2 py-0.5 text-[10px] font-semibold"
                              style="background: var(--teal-soft); color: var(--primary);">{{ $illness->total }}</span>
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed p-2 text-center"
                         style="border-color: var(--border); color: var(--ink-muted);">
                        <p class="font-semibold text-xs" style="color: var(--ink);">No diagnosis data yet</p>
                        <p class="text-[10px] mt-0.5">Populates after consultations.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 lg:gap-5">
        <div class="lg:col-span-8 space-y-4 lg:space-y-5">
            @if ($showResultsReady ?? false)
                @include('dashboard.partials.results-ready', [
                    'panelTitle' => 'Ready-to-Print Results',
                    'panelSubtitle' => 'Finalized consultations by RHU doctors that are ready for printing and distribution to patients',
                    'showFilters' => true,
                    'filterAction' => route('dashboard'),
                ])
            @endif

            <div class="animate-in opacity-0 delay-6 rounded-xl border p-4 lg:p-5"
                 style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
                <h2 class="font-display font-semibold text-lg lg:text-xl mb-4" style="color: var(--ink);">Recent activity</h2>
                <ul class="divide-y divide-[var(--border)] space-y-0">
                    @forelse($recentActivity as $log)
                        @php
                            $time = \Carbon\Carbon::parse($log->created_at)->format('M d, Y H:i');
                        @endphp

                        <li class="py-3.5 flex items-center justify-between text-sm transition-colors hover:bg-black/[0.01]">
                            <div class="flex items-center space-x-3">
                                <x-audit-badge :log="$log" />

                                <span style="color: var(--ink-muted);">
                                    <strong class="font-semibold text-[var(--ink)]">{{ $log->username ?: 'System' }}</strong>
                                    {{ in_array($log->action, ['created', 'updated', 'deleted'], true) ? '' : '-' }}
                                    {{ \App\Support\AuditTaxonomy::describeEvent($log->action, $log->table_name, $log->record_id) }}
                                </span>
                            </div>

                            <time class="text-xs font-medium shrink-0" style="color: var(--ink-subtle);">
                                {{ $time }}
                            </time>
                        </li>
                    @empty
                        <li class="py-3.5 text-center text-sm" style="color: var(--ink-muted);">
                            No activity has been recorded yet.
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="lg:col-span-4 space-y-4 lg:space-y-5">
            <div class="animate-in opacity-0 delay-6 rounded-xl border p-4 lg:p-5"
                 style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-semibold uppercase tracking-wider" style="color: var(--ink-muted);">On-duty staff</h3>
                    <span class="text-xs" style="color: var(--ink-subtle);">Today</span>
                </div>

                <div class="mt-3 max-h-60 overflow-y-auto space-y-3">
                    @forelse ($onDutyStaff as $staff)
                        <div class="flex items-center gap-3">
                            <div class="h-9 w-9 rounded-full flex items-center justify-center text-xs font-semibold flex-shrink-0"
                                 style="background: var(--teal-soft); color: var(--primary);">
                                {{ $staff['initials'] }}
                            </div>
                            <div class="min-w-0">
                                <div class="text-sm font-semibold truncate" style="color: var(--ink);">{{ $staff['name'] }}</div>
                                <div class="text-xs truncate" style="color: var(--ink-muted);">{{ $staff['role'] }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center space-y-3">
                            <div style="color: var(--ink-muted);">
                                <svg class="w-10 h-10 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292m0-5.292H8.646A4 4 0 0012 4.354zm0 0h3.354A4 4 0 0012 4.354m0 5.292L15.354 7.354M12 9.646l-3.354 2.292m0 0A4 4 0 004.354 12m0 0h5.292m-5.292 0a4 4 0 100 5.292m5.292 0H8.646m3.354-2.292l3.354 2.292m0 0A4 4 0 0019.646 12m0 0v5.292m0-5.292h-5.292"></path></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold" style="color: var(--ink);">No staff data</p>
                                <p class="text-xs mt-1" style="color: var(--ink-muted);">Add health workers to see them here</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="animate-in opacity-0 delay-6 rounded-xl border p-4 lg:p-5"
                 style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
                <h3 class="text-xs font-semibold uppercase tracking-wider" style="color: var(--ink-muted);">Quick actions</h3>
                <div class="mt-3 space-y-2">
                    <a href="{{ route('patients.create') }}"
                       class="w-full inline-flex items-center justify-center gap-2 px-3 py-2.5 rounded-lg text-xs font-semibold text-white transition-[transform,box-shadow] duration-200 hover:shadow-sm hover:scale-[1.01]"
                       style="background: var(--primary); box-shadow: var(--shadow-sm);">
                        <i class="fa-solid fa-user-plus mr-2" aria-hidden="true"></i>
                        Register patient
                    </a>
                    <a href="{{ route('consultations.index') }}"
                       class="w-full inline-flex items-center justify-center gap-2 px-3 py-2.5 rounded-lg text-xs font-semibold text-white transition-[transform,box-shadow] duration-200 hover:shadow-sm hover:scale-[1.01]"
                       style="background: var(--primary); box-shadow: var(--shadow-sm);">
                        <i class="fa-solid fa-stethoscope mr-2" aria-hidden="true"></i>
                        Log consultation
                    </a>
                    <a href="{{ route('immunizations.index') }}"
                       class="w-full inline-flex items-center justify-center gap-2 px-3 py-2.5 rounded-lg text-xs font-semibold border transition hover:bg-black/[0.02]"
                       style="border-color: var(--border); color: var(--primary);">
                        <i class="fa-solid fa-syringe mr-2" aria-hidden="true"></i>
                        View immunizations
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @vite('resources/js/charts.js')
@endpush