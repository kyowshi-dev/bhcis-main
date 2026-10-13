@extends('layouts.app')

@section('title', 'Consultation')

@section('content')
@php
    $clinicalReviewOpen = in_array($consultation->status, [\App\Enums\ConsultationStatus::DoctorReview->value, \App\Enums\ConsultationStatus::InProgress->value], true);
    $isFemalePatient = strtolower((string) ($patient->sex ?? '')) === 'female';
@endphp

<div class="min-h-screen bg-[var(--bg)]"
     x-data="{ showRetakeVitals: {{ session('vitals_form') === 'retake' ? 'true' : 'false' }} }"
     x-init="if (showRetakeVitals) $dispatch('open-vitals-modal')"
     x-on:open-retake-vitals.window="showRetakeVitals = true; $dispatch('open-vitals-modal')">
    <x-flash />

    @include('consultations.partials.show-header', [
        'consultation' => $consultation,
        'patient' => $patient,
        'canAcknowledgeIntake' => $canAcknowledgeIntake ?? false,
    ])

    <div class="mx-auto max-w-7xl px-4 pb-28 lg:px-6 lg:pb-6" x-data="consultationApp()">
        @include('consultations.partials.patient-ribbon', [
            'patient' => $patient,
            'consultation' => $consultation,
            'latestVitals' => $latestVitals,
            'allVitals' => $allVitals,
            'canEditComplaint' => ! in_array($consultation->status, \App\Enums\ConsultationStatus::terminalValues(), true),
            'clinicalReviewOpen' => $clinicalReviewOpen,
        ])

        <div class="mt-4 lg:mt-6 grid lg:grid-cols-12 gap-4">
            <main class="lg:col-span-7 space-y-4" id="mainContent">
                <section id="clinical" class="rounded-xl border bg-[var(--bg-surface)]" style="border-color: var(--border);">
                    <div class="border-b px-4 py-3" style="border-color: var(--border);">
                        <div class="flex items-center justify-between">
                            <h2 class="font-semibold text-base" style="color: var(--ink);">Clinical Review</h2>
                            <span class="text-xs px-2 py-0.5 rounded-full" :class="clinicalReviewOpen ? 'bg-teal-soft text-[var(--primary)]' : 'bg-amber-soft text-[var(--amber)]'" x-text="clinicalReviewOpen ? 'Open for entry' : 'Awaiting nurse validation'"></span>
                        </div>
                    </div>
                    <div class="p-4 space-y-4">
                        @include('consultations.partials.show-diagnoses', [
                            'diagnoses' => $diagnoses,
                            'canAddDiagnosis' => $canAddDiagnosis ?? false,
                            'clinicalReviewOpen' => $clinicalReviewOpen,
                        ])
                        @include('consultations.partials.show-prescriptions', [
                            'prescriptions' => $prescriptions,
                            'canAddPrescription' => $canAddPrescription ?? false,
                            'clinicalReviewOpen' => $clinicalReviewOpen,
                        ])
                    </div>
                </section>

                @if ($isFemalePatient)
                <section id="maternal" class="rounded-xl border bg-[var(--bg-surface)]" style="border-color: var(--border);" x-data="{ open: true }">
                    <div class="border-b px-4 py-3 flex items-center justify-between cursor-pointer" style="border-color: var(--border);" @click="open = !open" role="button" tabindex="0" @keydown.enter.prevent="open = !open" :aria-expanded="open.toString()">
                        <h2 class="font-semibold text-base" style="color: var(--ink);">Maternal Transactions</h2>
                        <i class="fa-solid text-[var(--ink-muted)] transition-transform" :class="open ? 'fa-chevron-down' : 'fa-chevron-right'" style="font-size: 12px;" aria-hidden="true"></i>
                    </div>
                    <div x-show="open" x-transition class="p-4 space-y-4">
                        @include('consultations.partials.show-maternal', [
                            'linkedPrenatalVisits' => $linkedPrenatalVisits,
                            'linkedPostnatal' => $linkedPostnatal,
                            'linkedFpVisits' => $linkedFpVisits,
                            'patient' => $patient,
                        ])
                    </div>
                </section>
                @endif
            </main>

            <aside class="lg:col-span-5 space-y-4 lg:sticky lg:top-24 lg:self-start lg:max-h-[calc(100vh-7rem)] lg:overflow-y-auto" id="sidebarContent">
                <section id="vitals" class="rounded-xl border bg-[var(--bg-surface)]" style="border-color: var(--border);">
                    @include('consultations.partials.show-vitals', [
                        'latestVitals' => $latestVitals,
                        'allVitals' => $allVitals,
                        'clinicalReviewOpen' => $clinicalReviewOpen,
                        'consultation' => $consultation,
                    ])
                </section>

                <section id="actions" class="rounded-xl border bg-[var(--bg-surface)]" style="border-color: var(--border);">
                    @include('consultations.partials.show-actions', [
                        'consultation' => $consultation,
                        'clinicalReviewOpen' => $clinicalReviewOpen,
                        'canReferExternally' => $canReferExternally ?? false,
                        'diagnoses' => $diagnoses,
                        'prescriptions' => $prescriptions,
                    ])
                </section>
            </aside>
        </div>

        @if (($clinicalReviewOpen || ($consultation->status === \App\Enums\ConsultationStatus::NurseReview->value && $canReferExternally)) && ! in_array($consultation->status, \App\Enums\ConsultationStatus::terminalValues(), true))
            <div class="fixed bottom-0 left-0 right-0 z-40 border-t bg-surface/95 px-4 py-3 backdrop-blur lg:hidden" style="border-color: var(--border);">
                <div class="mx-auto flex max-w-5xl flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs" style="color: var(--ink-muted);">
                        @if ($clinicalReviewOpen)
                            Add a diagnosis, then complete this visit.
                        @else
                            Nurse review is in progress. Refer the patient to a higher facility if needed.
                        @endif
                    </p>
                    <div class="flex flex-wrap items-center gap-2 justify-end">
                        @if ($canReferExternally)
                            <button type="button" onclick="openConsultationOutwardReferralWizard()" class="rounded-xl border px-4 py-2 text-sm font-semibold transition hover:bg-teal-soft" style="border-color: var(--primary); color: var(--primary);">
                                <i class="fa-solid fa-hospital mr-1" aria-hidden="true"></i> Refer
                            </button>
                        @endif
                        @if ($clinicalReviewOpen)
                            <button type="submit" form="finalizeForm" class="rounded-xl bg-[var(--primary)] px-5 py-2 text-sm font-semibold text-white transition hover:opacity-90" :disabled="diagnosisCount === 0" :class="{ 'opacity-50 cursor-not-allowed': diagnosisCount === 0 }">
                                Complete
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Re-take vitals modal: opens through the open-vitals-modal event (handled on the layout body) --}}
    <div x-show="showVitalsModal" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" style="display: none;" class="fixed inset-0 z-[100] overflow-y-auto bg-black/40 p-4" x-on:keydown.escape.window="showVitalsModal = false" role="dialog" aria-modal="true" aria-label="Re-take vitals">
        <div class="mx-auto my-4 w-full max-w-2xl rounded-2xl border bg-surface shadow-xl" style="border-color: var(--border);">
            <div class="flex items-center justify-between gap-3 border-b px-4 py-3" style="border-color: var(--border);">
                <div>
                    <h3 class="font-display text-lg font-semibold" style="color: var(--ink);">Re-take vitals</h3>
                    <p class="text-xs" style="color: var(--ink-muted);">Saves as a new version - earlier readings stay in history.</p>
                </div>
                <button type="button" @click="showVitalsModal = false" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-[var(--ink-muted)] hover:bg-black/5">Close</button>
            </div>

            <div class="max-h-[75vh] overflow-auto p-4">
                @if ($clinicalReviewOpen)
                    <form id="retakeVitalsForm" x-show="showRetakeVitals" x-transition style="display: none;"
                          action="{{ route('consultations.vitals.retake', $consultation->id) }}" method="POST"
                          class="space-y-4">
                        @csrf
                        <div>
                            <p class="text-sm font-semibold" style="color: var(--ink);">Record a new vitals version</p>
                            <p class="text-xs mt-0.5" style="color: var(--ink-muted);">Enter at least one reading - earlier versions stay in history.</p>
                        </div>

                        @include('consultations.partials.vitals-fields', [
                            'fieldPrefix' => 'retake_',
                            'required' => false,
                            'values' => [
                                'bp_systolic' => old('bp_systolic', $latestVitals?->rawDisplay('bp_systolic') ?? ''),
                                'bp_diastolic' => old('bp_diastolic', $latestVitals?->rawDisplay('bp_diastolic') ?? ''),
                                'weight' => old('weight', $latestVitals?->rawDisplay('weight_kg') ?? ''),
                                'height' => old('height', $latestVitals?->rawDisplay('height_cm') ?? ''),
                                'temperature' => old('temperature', $latestVitals?->rawDisplay('temperature_c') ?? ''),
                            ],
                        ])

                        <div>
                            <label for="retake_notes" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Notes <span class="font-normal">(optional)</span></label>
                            <textarea id="retake_notes" name="notes" rows="2" placeholder="e.g. Re-checked after 5 minutes of rest"
                                      class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">{{ old('notes') }}</textarea>
                            @error('notes')
                                <p class="mt-1 text-xs" style="color: var(--danger);">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                            <button type="button" @click="showVitalsModal = false"
                                    class="rounded-lg border px-4 py-2 text-sm font-semibold transition hover:bg-black/[0.03]"
                                    style="border-color: var(--border); color: var(--ink-muted);">Cancel</button>
                            <button type="submit"
                                    class="rounded-lg bg-[var(--primary)] px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90">Save new vitals version</button>
                        </div>
                    </form>
                @else
                    <p class="text-xs" style="color: var(--ink-muted);">Clinical vitals retake is available once the case is in the doctor queue.</p>
                @endif
            </div>
        </div>
    </div>

    @include('consultations.partials.show-modals', [
        'consultation' => $consultation,
        'patient' => $patient,
        'latestVitals' => $latestVitals,
    ])

    @push('scripts')
<script>
function consultationApp() {
    return {
        diagnosisCount: {{ $diagnoses->count() }},
        prescriptionCount: {{ $prescriptions->count() }},
        clinicalReviewOpen: {{ $clinicalReviewOpen ? 'true' : 'false' }},
        init() {
            document.addEventListener('keydown', (e) => {
                if (e.target.matches('input, textarea, select, [contenteditable="true"]') || e.target.isContentEditable) return;
                if (e.ctrlKey || e.metaKey || e.altKey) return;

                if (document.body.classList.contains('modal-open') || document.body.classList.contains('overflow-hidden')) return;

                const modals = window.Alpine?.store('modals');
                if (modals && (modals.diagnosis || modals.prescription || modals.outward || modals.consultation || modals.printReferral)) return;

                const vitalsOverlay = document.querySelector('[x-show="showVitalsModal"]');
                if (vitalsOverlay && vitalsOverlay.style.display !== 'none') return;

                switch (e.key) {
                    case 'd': case 'D':
                        if (this.clinicalReviewOpen && {{ ($canAddDiagnosis ?? false) ? 'true' : 'false' }}) {
                            e.preventDefault();
                            window.dispatchEvent(new CustomEvent('open-diagnosis-modal'));
                        }
                        break;
                    case 'p': case 'P':
                        if (this.clinicalReviewOpen && {{ ($canAddPrescription ?? false) ? 'true' : 'false' }}) {
                            e.preventDefault();
                            window.dispatchEvent(new CustomEvent('open-prescription-modal'));
                        }
                        break;
                    case 'v': case 'V':
                        if (this.clinicalReviewOpen) {
                            e.preventDefault();
                            window.dispatchEvent(new CustomEvent('open-retake-vitals'));
                        }
                        break;
                    case 'f': case 'F':
                        if (this.clinicalReviewOpen && this.diagnosisCount > 0) {
                            e.preventDefault();
                            document.getElementById('finalizeBtn')?.click();
                        }
                        break;
                    case 'r': case 'R':
                        if ({{ ($canReferExternally ?? false) ? 'true' : 'false' }}) {
                            e.preventDefault();
                            openConsultationOutwardReferralWizard();
                        }
                        break;
                }
            });
        }
    };
}
</script>
    @endpush
</div>
@endsection
