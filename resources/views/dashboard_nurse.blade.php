@extends('layouts.app')

@section('title', 'Nurse Dashboard')

@section('content')
<div class="space-y-5 lg:space-y-6">
    <div class="animate-in opacity-0 delay-1 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Nurse Dashboard</h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">E-check ang vitals sa pasyente ug ipasa sila ngadto sa linya sa doktor.</p>
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
            label="Patients assisted"
            :value="$consultationsToday"
            icon="fa-solid fa-user-check"
            tone="primary"
            :href="route('consultations.index')"
            :hrefLabel="'View visits'"
            :animateDelay="3"
        />

        <x-kpi-card
            label="Pending review"
            :value="$pendingValidationCount"
            icon="fa-solid fa-clipboard-list"
            :tone="$pendingValidationCount > 0 ? 'blue' : 'primary'"
            :href="route('consultations.index', ['status' => \App\Enums\ConsultationStatus::NurseReview->value])"
            :hrefLabel="$pendingValidationCount > 0 ? 'Open validation queue' : null"
            :zeroState="$pendingValidationCount === 0 ? 'All caught up' : null"
            :animateDelay="4"
        />

        <x-kpi-card
            label="Total active queue"
            :value="$intakePipelineCount"
            icon="fa-solid fa-people-group"
            tone="primary"
            :href="route('consultations.index')"
            :hrefLabel="'View pipeline'"
            :animateDelay="5"
        />
    </div>

    <div class="animate-in opacity-0 delay-5 rounded-xl border p-4 lg:p-5"
         style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm); border-left: 4px solid var(--accent);">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
            <div>
                <h2 class="font-display font-semibold text-lg" style="color: var(--ink);">Vitals Review Queue</h2>
                <p class="text-xs mt-1" style="color: var(--ink-muted);">
                    @if ($pendingValidationCount > 0)
                        {{ $pendingValidationCount }} intake{{ $pendingValidationCount !== 1 ? 's' : '' }} awaiting acknowledgment before doctor review.
                    @else
                        First come, first served. No pending intakes in the validation queue.
                    @endif
                </p>
            </div>
            <a href="{{ route('consultations.index', ['queue' => 1, 'status' => \App\Enums\ConsultationStatus::NurseReview->value]) }}" class="text-xs font-semibold hover:underline" style="color: var(--primary);">View All</a>
        </div>
        <ul class="space-y-2">
            @forelse ($validationQueue as $item)
                <li class="rounded-xl border px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                    style="border-color: var(--border); background: var(--bg-surface-elevated);">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold" style="color: var(--ink);">{{ fullName($item->last_name, $item->first_name) }}</p>
                        <p class="text-xs mt-0.5" style="color: var(--ink-muted);">
                            {{ \Carbon\Carbon::parse($item->created_at)->diffForHumans() }}
                            @if ($item->complaint_text)
                                · {{ Str::limit($item->complaint_text, 60) }}
                            @endif
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('consultations.show', $item->id) }}"
                           class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold border transition hover:bg-black/[0.02]"
                           style="border-color: var(--border); color: var(--primary);">
                            View Details
                        </a>
                        <form action="{{ route('consultations.acknowledge-intake', $item->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold text-white transition hover:opacity-90"
                                    style="background: var(--accent);">
                                <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                                Send to Doctor
                            </button>
                        </form>
                        <form action="{{ route('consultations.cancel', $item->id) }}" method="POST" class="inline" onsubmit="return confirmCancelIntake(this);">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold border transition hover:bg-black/[0.02]"
                                    style="border-color: var(--border); color: var(--ink-muted);">
                                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                Cancel
                            </button>
                        </form>
                    </div>
                </li>
            @empty
                <li class="rounded-xl border px-4 py-10 text-center" style="border-color: var(--border); background: var(--bg-surface-elevated);">
                    <div class="mx-auto w-12 h-12 rounded-full flex items-center justify-center"
                         style="background: var(--teal-soft); color: var(--primary);">
                        <i class="fa-solid fa-clipboard-check text-lg" aria-hidden="true"></i>
                    </div>
                    <p class="mt-3 text-sm font-semibold" style="color: var(--ink);">The validation queue is clear</p>
                    <p class="text-xs mt-1" style="color: var(--ink-muted);">New intakes will appear here as soon as patients are triaged.</p>
                </li>
            @endforelse
        </ul>
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

@push('scripts')
<script>
    function confirmCancelIntake(form) {
        Swal.fire({
            title: 'Cancel this intake?',
            text: 'The patient will be removed from the validation queue. This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: 'var(--danger)',
            cancelButtonColor: 'var(--ink-muted)',
            confirmButtonText: 'Yes, cancel intake',
            cancelButtonText: 'No, keep it',
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
        return false;
    }
</script>
@endpush