@php
    $tempAlert = (float) ($latestVitals?->temperature_c ?? 0) > 37.5;
    $bpAlert = ((int) ($latestVitals?->bp_systolic ?? 0) > 140) || ((int) ($latestVitals?->bp_diastolic ?? 0) > 90);
    $vitalsCount = $allVitals->count();
@endphp

<div class="p-4 space-y-4" x-data="{ historyOpen: false }">
    <div class="flex items-center justify-between">
        <h2 class="font-semibold text-sm" style="color: var(--ink);">Vitals</h2>
        @if ($clinicalReviewOpen)
            <button type="button" @click="$dispatch('open-retake-vitals')" class="px-2.5 py-1.5 rounded-lg text-[11px] font-medium transition flex items-center gap-1" style="background: var(--primary); color: white;" title="V - Re-take vitals">
                <i class="fa-solid fa-heart-pulse" aria-hidden="true"></i> Re-take
            </button>
        @endif
    </div>

    @if ($latestVitals)
        <div class="grid grid-cols-2 gap-2">
            <div class="rounded-lg border p-3" style="border-color: {{ $bpAlert ? 'var(--danger)' : 'var(--border)' }}; background: {{ $bpAlert ? 'var(--danger-soft)' : 'var(--bg)' }};">
                <div class="flex items-center gap-1.5">
                    <p class="text-[10px] font-semibold uppercase tracking-wide" style="color: {{ $bpAlert ? 'var(--danger)' : 'var(--ink-muted)' }};">BP</p>
                    @if ($bpAlert)
                        <i class="fa-solid fa-triangle-exclamation text-[10px]" style="color: var(--danger);" aria-hidden="true"></i>
                        <span class="sr-only">Elevated</span>
                    @endif
                </div>
                <p class="mt-1 font-display text-xl font-semibold tabular-nums" style="color: {{ $bpAlert ? 'var(--danger)' : 'var(--ink)' }};">
                    {{ $latestVitals->bp_systolic ?? '-' }}<span class="text-base font-medium text-ink-subtle">/</span>{{ $latestVitals->bp_diastolic ?? '-' }}
                </p>
                <p class="text-[10px] text-ink-muted">mmHg</p>
            </div>
            <div class="rounded-lg border p-3" style="border-color: {{ $tempAlert ? 'var(--danger)' : 'var(--border)' }}; background: {{ $tempAlert ? 'var(--danger-soft)' : 'var(--bg)' }};">
                <div class="flex items-center gap-1.5">
                    <p class="text-[10px] font-semibold uppercase tracking-wide" style="color: {{ $tempAlert ? 'var(--danger)' : 'var(--ink-muted)' }};">Temp</p>
                    @if ($tempAlert)
                        <i class="fa-solid fa-triangle-exclamation text-[10px]" style="color: var(--danger);" aria-hidden="true"></i>
                        <span class="sr-only">Elevated</span>
                    @endif
                </div>
                <p class="mt-1 font-display text-xl font-semibold tabular-nums" style="color: {{ $tempAlert ? 'var(--danger)' : 'var(--ink)' }};">
                    {{ $latestVitals->rawDisplay('temperature_c') ?? '-' }}<span class="text-base font-medium text-ink-subtle">&deg;</span>
                </p>
                <p class="text-[10px] text-ink-muted">&deg;C</p>
            </div>
            <div class="rounded-lg border p-3" style="border-color: var(--border); background: var(--bg);">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-ink-muted">Weight</p>
                <p class="mt-1 font-display text-xl font-semibold tabular-nums" style="color: var(--ink);">
                    {{ $latestVitals->rawDisplay('weight_kg') ?? '-' }}
                </p>
                <p class="text-[10px] text-ink-muted">kg</p>
            </div>
            <div class="rounded-lg border p-3" style="border-color: var(--border); background: var(--bg);">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-ink-muted">Height</p>
                <p class="mt-1 font-display text-xl font-semibold tabular-nums" style="color: var(--ink);">
                    {{ $latestVitals->rawDisplay('height_cm') ?? '-' }}
                </p>
                <p class="text-[10px] text-ink-muted">cm</p>
            </div>
        </div>

        <p class="text-[11px] text-ink-muted">
            Taken {{ \Carbon\Carbon::parse($latestVitals->created_at)->format('M j, g:i A') }}
            &middot; {{ $latestVitals->phase === 'clinical' ? 'Clinical re-take' : 'Triage' }}
        </p>

        @if ($vitalsCount > 1)
            <div class="border-t pt-3" style="border-color: var(--border);">
                <button type="button" @click="historyOpen = !historyOpen" :aria-expanded="historyOpen.toString()" aria-controls="vitalsHistory"
                        class="flex w-full items-center justify-between text-xs font-medium text-ink-muted hover:text-ink transition">
                    <span>History ({{ $vitalsCount }} versions)</span>
                    <i class="fa-solid transition-transform" :class="historyOpen ? 'fa-chevron-up' : 'fa-chevron-down'" aria-hidden="true"></i>
                </button>
                <div id="vitalsHistory" x-show="historyOpen" x-transition.duration.150ms class="mt-2 space-y-1 pr-1">
                    @foreach ($allVitals as $vitalVersion)
                        @php
                            $vTempAlert = (float) ($vitalVersion->temperature_c ?? 0) > 37.5;
                            $vBpAlert = ((int) ($vitalVersion->bp_systolic ?? 0) > 140) || ((int) ($vitalVersion->bp_diastolic ?? 0) > 90);
                            $isTriageBaseline = ($vitalVersion->phase ?? null) === 'triage';
                            $deleteDisabled = $isTriageBaseline || $vitalsCount <= 1;
                            $deleteReason = $isTriageBaseline
                                ? 'The triage baseline version cannot be deleted.'
                                : 'Only one version exists - re-take vitals before deleting.';
                        @endphp
                        <div class="rounded-lg border px-2.5 py-2 text-[11px]" style="border-color: var(--border); background: var(--bg);">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="font-medium text-ink">{{ \Carbon\Carbon::parse($vitalVersion->created_at)->format('M j, g:i A') }}</span>
                                    <span class="rounded px-1 py-0.5 text-[9px] font-semibold uppercase tracking-wide {{ $vitalVersion->phase === 'clinical' ? 'bg-teal-soft text-[var(--primary)]' : 'border border-border text-ink-muted' }}">
                                        {{ $vitalVersion->phase ?? 'triage' }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-0.5 shrink-0">
                                    <button type="button" @click="$dispatch('open-edit-vital', { id: {{ $vitalVersion->id }}, bp_systolic: {{ $vitalVersion->bp_systolic ?? '' }}, bp_diastolic: {{ $vitalVersion->bp_diastolic ?? '' }}, temperature: {{ $vitalVersion->temperature_c ?? '' }}, weight: {{ $vitalVersion->weight_kg ?? '' }}, height: {{ $vitalVersion->height_cm ?? '' }}, notes: {{ \Illuminate\Support\Js::from($vitalVersion->notes ?? '') }} })"
                                            class="rounded p-1.5 text-ink-subtle transition hover:text-[var(--primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-blue"
                                            title="Edit this version" aria-label="Edit vitals version from {{ \Carbon\Carbon::parse($vitalVersion->created_at)->format('M j, g:i A') }}">
                                        <i class="fa-solid fa-pen-to-square text-[11px]" aria-hidden="true"></i>
                                    </button>
                                    <form action="{{ route('consultations.vitals.delete', ['consultation' => $consultation->id, 'vitalId' => $vitalVersion->id]) }}" method="POST" class="inline" onsubmit="return confirmVitalsDelete(this);">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded p-1.5 text-ink-subtle transition hover:text-[var(--danger)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-blue disabled:cursor-not-allowed disabled:opacity-40"
                                                @if ($deleteDisabled) disabled title="{{ $deleteReason }}" @endif
                                                aria-label="Delete vitals version from {{ \Carbon\Carbon::parse($vitalVersion->created_at)->format('M j, g:i A') }}{{ $deleteDisabled ? ' - ' . $deleteReason : '' }}">
                                            <i class="fa-solid fa-trash text-[11px]" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="mt-1 flex flex-wrap gap-x-3 gap-y-0.5 text-ink-muted">
                                <span style="color: {{ $vBpAlert ? 'var(--danger)' : 'inherit' }};">BP {{ $vitalVersion->bp_systolic ?? '-' }}/{{ $vitalVersion->bp_diastolic ?? '-' }}</span>
                                <span style="color: {{ $vTempAlert ? 'var(--danger)' : 'inherit' }};">Temp {{ $vitalVersion->rawDisplay('temperature_c') ?? '-' }}&deg;C</span>
                                <span>Wt {{ $vitalVersion->rawDisplay('weight_kg') ?? '-' }}kg</span>
                                <span>Ht {{ $vitalVersion->rawDisplay('height_cm') ?? '-' }}cm</span>
                            </div>
                            <div class="mt-1 flex items-center justify-between gap-2">
                                <p class="truncate text-ink-muted">{{ fullName($vitalVersion->captured_by_last_name ?? null, $vitalVersion->captured_by_first_name ?? null) ?: 'N/A' }}</p>
                            </div>
                            @if ($vitalVersion->notes)
                                <p class="mt-1 truncate text-ink-muted" title="{{ $vitalVersion->notes }}">{{ $vitalVersion->notes }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @else
        <div class="py-6 text-center rounded-lg border-2 border-dashed" style="border-color: var(--border);">
            <i class="fa-solid fa-heart-pulse text-3xl mb-2" style="color: var(--ink-subtle);"></i>
            <p class="text-sm" style="color: var(--ink-muted);">No vitals recorded</p>
        </div>
    @endif
</div>
