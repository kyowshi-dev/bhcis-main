<div class="sticky top-0 z-30 border-b bg-surface/95 backdrop-blur px-4 py-3 lg:px-6" style="border-color: var(--border);">
    <div class="mx-auto max-w-7xl flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
        <div class="flex flex-col lg:flex-row lg:items-center gap-3">
            <div>
                <h1 class="font-display text-xl lg:text-2xl font-semibold" style="color: var(--ink);">Consultation <span class="font-mono text-[var(--primary)]">#{{ $consultation->id }}</span></h1>
                <p class="text-xs mt-0.5" style="color: var(--ink-muted);">
                    {{ \Carbon\Carbon::parse($consultation->created_at)->format(\App\Helpers\DateFormat::DATETIME_AMPM) }}
                    <span aria-hidden="true"> · </span>
                    {{ fullName($patient->last_name, $patient->first_name, $patient->middle_name, $patient->suffix) }}
                    <span aria-hidden="true"> · </span>
                    {{ \Carbon\Carbon::parse($patient->date_of_birth)->age }} y/o
                    <span aria-hidden="true"> · </span>
                    {{ $patient->sex }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold" style="{{ $consultation->status_style }}">{{ $consultation->status_label }}</span>
                @if ($consultation->purpose_of_visit)
                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold" style="background: var(--teal-soft); color: var(--primary);">{{ $consultation->purpose_of_visit }}</span>
                @endif
                @if ($consultation->escalated_at)
                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold" style="background: var(--amber-soft); color: var(--amber);">
                        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Flagged
                    </span>
                @endif
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($consultation->status === \App\Enums\ConsultationStatus::NurseReview->value && $canAcknowledgeIntake)
                <form action="{{ route('consultations.acknowledge-intake', $consultation->id) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="rounded-xl px-3 py-2 text-xs font-semibold text-white transition hover:opacity-90" style="background: var(--accent);">
                        <i class="fa-solid fa-check mr-1"></i> Send to Doctor
                    </button>
                </form>
            @endif
            <a href="{{ route('patients.show', $patient->id) }}" class="rounded-lg border px-3 py-2 text-xs font-semibold transition hover:bg-black/[0.03]" style="border-color: var(--border); color: var(--ink);">
                <i class="fa-solid fa-arrow-left mr-1"></i> Patient
            </a>
            <a href="{{ route('consultations.index') }}" class="rounded-lg border px-3 py-2 text-xs font-semibold transition hover:bg-black/[0.03]" style="border-color: var(--border); color: var(--ink);">
                <i class="fa-solid fa-clock-rotate-left mr-1"></i> History
            </a>
            @if (in_array($consultation->status, \App\Enums\ConsultationStatus::terminalValues(), true) && auth()->user()->canPrintHandout())
                    <a href="{{ route('consultations.handout', $consultation->id) }}" target="_blank" rel="noopener" class="rounded-lg px-3 py-2 text-xs font-semibold text-white transition hover:opacity-90" style="background: var(--primary);">
                        <i class="fa-solid fa-print mr-1"></i> Print
                    </a>
            @endif
        </div>
    </div>
</div>