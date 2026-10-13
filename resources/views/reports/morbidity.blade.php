@extends('layouts.app')

@section('title', 'Morbidity Report')

@section('content')
<div class="space-y-4 lg:space-y-6">
    <x-page-header
        title="Month Morbidity Disease Report"
        subtitle="Generate and view monthly morbidity reports."
        icon="fa-solid fa-chart-bar"
    >
        <x-slot name="actions">
            <a href="{{ route('reports.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-border text-xs lg:text-sm font-medium text-ink transition hover:bg-black/5">Back to Reports</a>
        </x-slot>
    </x-page-header>
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4 items-end">
                    <div>
                        <label for="month" class="block text-xs font-medium" style="color: var(--ink-muted);">Month</label>
                        <select id="month" name="month" class="w-full rounded-lg border py-2 text-xs lg:text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                            @foreach (range(1, 12) as $m)
                                <option value="{{ $m }}" @selected($month === $m)>{{ \Carbon\Carbon::createFromDate(null, $m, 1)->format('M') }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="year" class="block text-xs font-medium" style="color: var(--ink-muted);">Year</label>
                        <input type="number" id="year" name="year" value="{{ $year }}" min="2020" max="{{ date('Y') + 1 }}" class="w-full rounded-lg border py-2 text-xs lg:text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                    </div>

                    <div>
                        <label for="sex" class="block text-xs font-medium" style="color: var(--ink-muted);">Sex</label>
                        <select id="sex" name="sex" class="w-full rounded-lg border py-2 text-xs lg:text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                            <option value="All" @selected(($sex ?? 'All') === 'All')>All Sex</option>
                            <option value="M" @selected(($sex ?? '') === 'M')>Male</option>
                            <option value="F" @selected(($sex ?? '') === 'F')>Female</option>
                        </select>
                    </div>

                    <div>
                        <label for="zone" class="block text-xs font-medium" style="color: var(--ink-muted);">Zone</label>
                        <select id="zone" name="zone" class="w-full rounded-lg border py-2 text-xs lg:text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                            <option value="">All Zones</option>
                            @if(!empty($zones))
                                @foreach($zones as $z)
                                    <option value="{{ $z->id }}" @selected((string)($selectedZone ?? '') === (string)$z->id)>{{ $z->zone_number }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-end gap-2">
                    <div class="flex-1">
                        <label for="age_group" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Age group</label>
                        <select id="age_group" name="age_group" class="w-full rounded-lg border py-2 text-xs lg:text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                            <option value="all" @selected(($age_group ?? 'all') === 'all')>All ages</option>
                            <optgroup label="Infants">
                                <option value="infant_0_6d" @selected(($age_group ?? '') === 'infant_0_6d')>0–6 days</option>
                                <option value="infant_7_28d" @selected(($age_group ?? '') === 'infant_7_28d')>7–28 days</option>
                                <option value="infant_29_11m" @selected(($age_group ?? '') === 'infant_29_11m')>29 days – 11 months</option>
                            </optgroup>
                            <optgroup label="Children">
                                <option value="child_1_4" @selected(($age_group ?? '') === 'child_1_4')>1–4 years</option>
                                <option value="child_5_9" @selected(($age_group ?? '') === 'child_5_9')>5–9 years</option>
                                <option value="child_10_14" @selected(($age_group ?? '') === 'child_10_14')>10–14 years</option>
                            </optgroup>
                            <optgroup label="Adults & Elderly">
                                @foreach(range(15, 65, 5) as $start)
                                    @php $end = $start + 4; @endphp
                                    <option value="{{ $start }}_{{ $end }}" @selected(($age_group ?? '') === "{$start}_{$end}")>{{ $start }}–{{ $end }} years</option>
                                @endforeach
                                <option value="70_plus" @selected(($age_group ?? '') === '70_plus')>≥ 70 years</option>
                            </optgroup>
                        </select>
                    </div>

                    <div class="sm:w-40">
                        <button
                            type="button"
                            x-on:click="downloadPdf()"
                            class="w-full px-3 lg:px-4 py-2 rounded-lg text-white text-xs lg:text-sm font-medium transition-colors hover:opacity-90"
                            style="background: var(--primary);">
                            Download PDF
                        </button>
                    </div>
                    <div class="sm:w-40">
                        <a href="{{ route('reports.morbidity.export.csv', ['month' => $month, 'year' => $year, 'sex' => $sex, 'zone' => $selectedZone, 'age_group' => $age_group]) }}"
                           class="block w-full text-center px-3 lg:px-4 py-2 rounded-lg text-white text-xs lg:text-sm font-medium transition-colors hover:opacity-90"
                           style="background: var(--primary);">
                            Export CSV
                        </a>
                    </div>
                </div>
            </form>
        </div>

    @include('reports.partials._fhsis-header', [
    'formCode' => 'M2',
    'reportDate' => $reportDate,
    'projectedPopulation' => \App\Models\ApplicationSetting::get('projected_population'),
    'mode' => 'screen',
])

@php
    $ageBands = $matrix['ageBands'] ?? [];
    $sexLabels = $matrix['sexLabels'] ?? ['Male', 'Female', 'Total'];
    $matrixRows = $matrix['rows'] ?? collect();
    $leadingCauses = $leadingCauses ?? [];
@endphp

{{-- M2 Matrix Table --}}
<div class="mb-6">
    <h3 class="text-sm font-semibold mb-3 text-ink">M2 Matrix: Cases by Diagnosis, Age Band, and Sex</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-xs lg:text-sm">
            <thead class="bg-teal-soft border-b border-border sticky top-0 z-10">
                <tr>
                    <th class="px-2 lg:px-3 py-2 font-semibold text-ink w-24 whitespace-nowrap sticky left-0 z-20">Diagnosis</th>
                    <th class="px-2 lg:px-3 py-2 font-semibold text-ink w-16 whitespace-nowrap sticky left-24 z-20">ICD</th>
                    <th class="px-1 lg:px-2 py-2 font-semibold text-ink w-14 whitespace-nowrap text-center">Total</th>
                    @foreach ($ageBands as $band)
                        @foreach ($sexLabels as $sexLabel)
                            <th class="px-1 lg:px-2 py-2 font-semibold text-ink w-12 whitespace-nowrap text-center">{{ $band }}<br>{{ $sexLabel }}</th>
                        @endforeach
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse ($matrixRows as $row)
                    <tr class="hover:bg-teal-soft/50">
                        <td class="px-2 lg:px-3 py-2 text-ink sticky left-0 z-10">{{ $row->diagnosis_name }}</td>
                        <td class="px-2 lg:px-3 py-2 text-ink sticky left-24 z-10">{{ $row->diagnosis_code }}</td>
                        <td class="px-1 lg:px-2 py-2 text-center font-semibold text-ink">{{ number_format($row->total_cases) }}</td>
                        @foreach ($ageBands as $band)
                            @foreach ($sexLabels as $sexLabel)
                                <td class="px-1 lg:px-2 py-2 text-center text-ink">
                                    {{ number_format($row->{"{$band}_{$sexLabel}"} ?? 0) }}
                                </td>
                            @endforeach
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 3 + count($ageBands) * count($sexLabels) }}" class="px-4 py-6 lg:py-8 text-center text-ink-muted text-sm">No morbidity data for this period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Leading Causes Sections A.2.a-g --}}
<div class="space-y-6">
    @foreach (['all_ages', 'males', 'females', 'under_5', 'adolescents_10_19', 'seniors_60_plus', 'all_ages_by_rate'] as $sectionKey)
        @if (isset($leadingCauses[$sectionKey]))
            @php
                $section = $leadingCauses[$sectionKey];
                $rows = $section['rows'] ?? collect();
                $isRateSection = $sectionKey === 'all_ages_by_rate';
            @endphp
            <div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border);">
                <h4 class="text-xs lg:text-sm font-semibold mb-3" style="color: var(--ink);">{{ $section['label'] }}</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-xs lg:text-sm">
                        <thead class="bg-teal-soft border-b border-border">
                            <tr>
                                <th class="px-2 lg:px-3 py-2 font-semibold text-ink w-10 whitespace-nowrap">Rank</th>
                                <th class="px-2 lg:px-3 py-2 font-semibold text-ink w-16 whitespace-nowrap">ICD</th>
                                <th class="px-2 lg:px-3 py-2 font-semibold text-ink">Diagnosis</th>
                                <th class="px-2 lg:px-3 py-2 font-semibold text-ink w-14 whitespace-nowrap text-right">Cases</th>
                                @if ($isRateSection)
                                    <th class="px-2 lg:px-3 py-2 font-semibold text-ink w-24 whitespace-nowrap text-right">Rate/100k</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach ($rows->take(10) as $row)
                                <tr class="hover:bg-teal-soft/50">
                                    <td class="px-2 lg:px-3 py-2 font-medium text-ink">{{ $row->rank ?? $loop->iteration }}</td>
                                    <td class="px-2 lg:px-3 py-2 text-ink">{{ $row->diagnosis_code }}</td>
                                    <td class="px-2 lg:px-3 py-2 text-ink">{{ $row->diagnosis_name }}</td>
                                    <td class="px-2 lg:px-3 py-2 text-right font-semibold text-ink">{{ number_format($row->case_count) }}</td>
                                    @if ($isRateSection)
                                        <td class="px-2 lg:px-3 py-2 text-right text-ink">{{ number_format($row->rate_per_100k ?? 0, 2) }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endforeach
</div>

{{-- Keep original ranked table as fallback --}}
@if ($rows->isNotEmpty())
<div class="mt-6 rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border);">
    <h3 class="text-sm font-semibold mb-3 text-ink">Ranked Leading Causes (All Ages)</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-xs lg:text-sm">
            <thead class="bg-teal-soft border-b border-border">
                <tr>
                    <th class="px-2 lg:px-4 py-2 lg:py-3 font-semibold text-ink w-12 lg:w-16 whitespace-nowrap">Rank</th>
                    <th class="px-2 lg:px-4 py-2 lg:py-3 font-semibold text-ink w-20 lg:w-24 whitespace-nowrap">ICD Code</th>
                    <th class="px-2 lg:px-4 py-2 lg:py-3 font-semibold text-ink">Diagnosis / Cause</th>
                    <th class="px-2 lg:px-4 py-2 lg:py-3 font-semibold text-ink w-20 lg:w-24 text-right whitespace-nowrap">Cases</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse ($rows as $rank => $row)
                    <tr class="hover:bg-teal-soft/50">
                        <td class="px-2 lg:px-4 py-2 lg:py-3 font-medium text-ink">{{ $rank + 1 }}</td>
                        <td class="px-2 lg:px-4 py-2 lg:py-3 text-ink">{{ $row->diagnosis_code }}</td>
                        <td class="px-2 lg:px-4 py-2 lg:py-3 text-ink">{{ $row->diagnosis_name }}</td>
                        <td class="px-2 lg:px-4 py-2 lg:py-3 text-right font-semibold text-ink">{{ number_format($row->case_count) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 lg:py-8 text-center text-ink-muted text-sm">No morbidity data for this period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-3 lg:px-4 py-2 lg:py-3 bg-teal-soft border-t border-border text-xs lg:text-sm font-semibold text-ink mt-3">
        Total cases: {{ number_format($totalCases) }}
    </div>
</div>
@endif

<script>
(function () {
    const form = document.getElementById('morbidityFilterForm');
    if (!form) return;

    const submitForm = function () {
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
            return;
        }
        form.submit();
    };

    form.addEventListener('change', function (e) {
        const target = e.target;
        if (!target) return;

        if (['month', 'year', 'sex', 'zone', 'age_group'].includes(target.id)) {
            submitForm();
        }
    });
})();

function downloadPdf() {
    const url = new URL('{{ route("reports.morbidity.download") }}', window.location.origin);
    const monthEl = document.getElementById('month');
    const yearEl = document.getElementById('year');
    const sexEl = document.getElementById('sex');
    const zoneEl = document.getElementById('zone');
    const ageEl = document.getElementById('age_group');

    if (monthEl) url.searchParams.set('month', monthEl.value);
    if (yearEl) url.searchParams.set('year', yearEl.value);
    if (sexEl) url.searchParams.set('sex', sexEl.value);
    if (zoneEl && zoneEl.value) url.searchParams.set('zone', zoneEl.value);
    if (ageEl) url.searchParams.set('age_group', ageEl.value);

    window.open(url.toString(), '_blank');
}
</script>
@endsection
