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

    <div class="animate-in opacity-0 delay-2 rounded-xl border p-4" x-data="patientSearch()"
         style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
        <div class="flex flex-col lg:flex-row lg:items-center gap-3">
            <div class="relative flex-1 min-w-0">
                <label for="nurse-patient-search" class="sr-only">Search patients by name</label>
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none" style="color: var(--ink-subtle);" :style="loading && 'color: var(--primary)'">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                </span>
                <input id="nurse-patient-search" type="search" x-model="query" @input.debounce.300ms="search()"
                       placeholder="Search patients by name..."
                       class="w-full pl-10 pr-4 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 transition"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);"
                       autocomplete="off">
            </div>
            <a href="{{ route('patients.create') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold text-white transition hover:opacity-90 shrink-0" style="background: var(--primary);">
                <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                Enrol New Patient
            </a>
        </div>
        <p x-show="loading" x-transition class="mt-3 text-xs" style="color: var(--ink-muted);" role="status">Searching…</p>
        <div x-show="results.length > 0" x-transition class="mt-3 rounded-lg border overflow-hidden" style="display: none; border-color: var(--border); background: var(--bg-surface-elevated); box-shadow: var(--shadow-md);">
            <ul>
                <template x-for="patient in results" :key="patient.id">
                    <li class="border-b last:border-0 transition-colors hover:bg-black/[0.03]">
                        <button type="button" @click="window.openConsultationCreateModal(patient.id)" class="block w-full text-left px-4 py-2.5">
                            <div class="font-medium text-sm" style="color: var(--ink);" x-text="patient.text"></div>
                            <div class="text-xs mt-0.5" style="color: var(--ink-muted);">
                                <span x-text="patient.subtext"></span>
                                <span class="font-semibold" style="color: var(--primary);"> - Start intake</span>
                            </div>
                        </button>
                    </li>
                </template>
            </ul>
        </div>
        <div x-show="query.length > 1 && results.length === 0 && !loading" x-transition class="mt-3 rounded-lg border p-6 text-center" style="display: none; border-color: var(--border); background: var(--bg-surface);">
            <div class="flex justify-center mb-2"><i class="fa-solid fa-user-plus text-3xl" style="color: var(--ink-subtle);" aria-hidden="true"></i></div>
            <p class="text-sm font-medium" style="color: var(--ink);">No patient found</p>
            <p class="text-xs mt-1 mb-3" style="color: var(--ink-muted);">Try searching with a different name or ID</p>
            <a href="{{ route('patients.create') }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold text-white transition hover:opacity-90" style="background: var(--primary);"><i class="fa-solid fa-plus" aria-hidden="true"></i> Enrol a new patient</a>
        </div>
    </div>

    <div class="animate-in opacity-0 delay-3 rounded-xl border p-4"
         style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
        <h3 class="text-xs font-semibold uppercase tracking-wider" style="color: var(--ink-muted);">Quick actions</h3>
        <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-2">
            @if (auth()->user()?->hasPermission('nurse_batch_entry'))
                <a href="{{ route('batch-entry.index') }}"
                   class="inline-flex items-center justify-center gap-2 px-3 py-2.5 rounded-lg text-xs font-semibold text-white transition-[transform,box-shadow] duration-200 hover:shadow-sm hover:scale-[1.01]"
                   style="background: var(--primary); box-shadow: var(--shadow-sm);">
                    <i class="fa-solid fa-keyboard" aria-hidden="true"></i>
                    Batch data entry
                </a>
            @endif
            @if (auth()->user()?->hasPermission('referrals'))
                <a href="{{ route('referrals.index') }}"
                   class="inline-flex items-center justify-center gap-2 px-3 py-2.5 rounded-lg text-xs font-semibold border transition hover:bg-black/[0.02]"
                   style="border-color: var(--border); color: var(--primary);">
                    <i class="fa-solid fa-arrow-right-arrow-left" aria-hidden="true"></i>
                    View referrals
                </a>
            @endif
            @if (auth()->user()?->hasPermission('immunizations'))
                <a href="{{ route('immunizations.index') }}"
                   class="inline-flex items-center justify-center gap-2 px-3 py-2.5 rounded-lg text-xs font-semibold border transition hover:bg-black/[0.02]"
                   style="border-color: var(--border); color: var(--primary);">
                    <i class="fa-solid fa-syringe" aria-hidden="true"></i>
                    View immunizations
                </a>
            @endif
        </div>
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
    function patientSearch() {
        return {
            query: '',
            results: [],
            loading: false,
            async search() {
                if (this.query.length < 2) { this.results = []; return; }
                this.loading = true;
                try {
                    const response = await safeFetch(`{{ route('search.patients') }}?query=${encodeURIComponent(this.query)}`);
                    this.results = response.ok ? await response.json() : [];
                } catch (e) {
                    console.error('Search failed:', e);
                    this.results = [];
                }
                this.loading = false;
            },
        };
    }

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