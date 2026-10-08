@props([
    'panelTitle' => 'Ready-to-Print Results',
    'panelSubtitle' => 'Finalized consultations ready for printing.',
    'showFilters' => false,
    'filterAction' => '#',
    'resultsReady' => [],
    'resultsReadyCount' => 0,
    'resultsFilters' => ['query' => '', 'from' => '', 'to' => ''],
])

<div class="animate-in opacity-0 delay-6 rounded-xl border p-4 lg:p-5"
     style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <div>
            <h2 class="font-display font-semibold text-base lg:text-lg" style="color: var(--ink);">{{ $panelTitle }}</h2>
            <p class="text-sm mt-0.5" style="color: var(--ink-muted);">{{ $panelSubtitle }}</p>
        </div>
        @if ($showFilters)
            <form method="GET" action="{{ $filterAction }}" class="flex flex-wrap items-center gap-2 shrink-0">
                <input type="text" name="results_query" value="{{ $resultsFilters['query'] }}" placeholder="Search patient..."
                       class="flex-1 min-w-[180px] px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 transition"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                <input type="date" name="results_from" value="{{ $resultsFilters['from'] }}"
                       class="px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 transition"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                <input type="date" name="results_to" value="{{ $resultsFilters['to'] }}"
                       class="px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 transition"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                <button type="submit" class="px-4 py-2 rounded-xl text-white text-sm font-semibold transition hover:opacity-90"
                        style="background: var(--primary);">Filter</button>
                <a href="{{ $filterAction }}" class="px-3 py-2 rounded-xl text-sm font-semibold transition hover:underline"
                   style="color: var(--primary);">Clear</a>
            </form>
        @endif
    </div>

    @if ($resultsReady->isEmpty())
        <div class="rounded-xl border border-dashed p-8 text-center" style="background: var(--bg-surface); border-color: var(--border);">
            <i class="fa-solid fa-file-lines text-3xl mb-3" style="color: var(--ink-subtle);" aria-hidden="true"></i>
            <p class="font-semibold" style="color: var(--ink);">No results ready</p>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">No finalized consultations match your filters.</p>
        </div>
    @else
        <div class="space-y-2">
            @foreach ($resultsReady as $result)
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-3 rounded-lg border transition-colors hover:bg-black/[0.02]"
                     style="border-color: var(--border); background: var(--bg-surface);">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="font-semibold text-sm truncate" style="color: var(--ink);">
                                {{ fullName($result->last_name ?? '', $result->first_name ?? '', null, null) }}
                                <span class="text-xs font-medium" style="color: var(--ink-muted);">
                                    ({{ \App\Helpers\PatientCode::format((int) ($result->patient_id ?? 0)) }})
                                </span>
                            </p>
                            @if (! empty($result->diagnosis_summary))
                                <p class="text-xs mt-0.5 truncate" style="color: var(--ink-muted);">{{ $result->diagnosis_summary }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold"
                              style="background: var(--teal-soft); color: var(--primary);">
                            <i class="fa-solid fa-calendar" aria-hidden="true"></i>
                            {{ \Carbon\Carbon::parse($result->updated_at)->format('M d, Y H:i') }}
                        </span>
                        <a href="{{ route('consultations.show', $result->id) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition"
                           style="background: var(--teal-soft); color: var(--primary);">
                            View <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                        <a href="{{ route('consultations.handout', $result->id) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold border transition hover:bg-black/[0.02]"
                           style="border-color: var(--border); color: var(--primary);"
                           target="_blank">
                            <i class="fa-solid fa-print" aria-hidden="true"></i> Print
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($resultsReadyCount > $resultsReady->count())
            <div class="mt-3 text-center">
                <a href="{{ route('dashboard') }}" class="text-xs font-semibold hover:underline" style="color: var(--primary);">
                    View all {{ $resultsReadyCount }} results &rarr;
                </a>
            </div>
        @endif
    @endif
</div>