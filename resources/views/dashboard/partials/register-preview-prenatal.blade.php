@php
    $total = $total ?? $rows->count();
@endphp

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
    <div>
        <h2 class="font-display font-semibold text-lg lg:text-xl" style="color: var(--ink);">Prenatal register</h2>
        <p class="text-sm mt-0.5" style="color: var(--ink-muted);">Active pregnancies, EDC, and next visit schedule.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2 shrink-0">
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--teal-soft); color: var(--primary);">{{ $total }} total</span>
        <a href="{{ route('maternal.prenatal.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold transition hover:bg-black/[0.03]"
           style="border: 1px solid var(--border); color: var(--primary);">
            Open full register <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </a>
    </div>
</div>

@if ($rows->isEmpty())
    <div class="rounded-xl border border-dashed p-10 text-center" style="background: var(--bg-surface); border-color: var(--border);">
        <i class="fa-solid fa-baby-carriage text-3xl mb-3" style="color: var(--ink-subtle);" aria-hidden="true"></i>
        <p class="font-semibold" style="color: var(--ink);">No active pregnancies</p>
        <p class="text-sm mt-1" style="color: var(--ink-muted);">Open a patient profile and register a pregnancy under Maternal Care.</p>
        @if (auth()->user()?->hasPermission('patients'))
            <a href="{{ route('patients.index') }}" class="inline-flex items-center justify-center mt-4 px-4 py-2.5 rounded-xl text-white text-sm font-semibold transition hover:shadow-md"
               style="background: var(--primary);">
                <i class="fa-solid fa-user-injured mr-1.5" aria-hidden="true"></i> Browse patients
            </a>
        @endif
    </div>
@else
    <div class="rounded-xl border overflow-hidden" style="background: var(--bg-surface); border-color: var(--border);">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="border-b" style="background: var(--teal-soft);">
                    <tr class="text-xs uppercase tracking-wide" style="color: var(--ink-muted);">
                        <th class="px-4 py-3 font-semibold whitespace-nowrap">Patient</th>
                        <th class="px-4 py-3 font-semibold whitespace-nowrap hidden md:table-cell">Zone</th>
                        <th class="px-4 py-3 font-semibold whitespace-nowrap">Risk</th>
                        <th class="px-4 py-3 font-semibold whitespace-nowrap">G/P</th>
                        <th class="px-4 py-3 font-semibold whitespace-nowrap">EDC</th>
                        <th class="px-4 py-3 font-semibold whitespace-nowrap">AOG</th>
                        <th class="px-4 py-3 font-semibold whitespace-nowrap">Visits</th>
                        <th class="px-4 py-3 font-semibold whitespace-nowrap">Next visit</th>
                        <th class="px-4 py-3 font-semibold whitespace-nowrap text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y" style="border-color: var(--border);">
                    @foreach ($rows as $pregnancy)
                        @php
                            $nextVisit = $pregnancy->latest_next_visit_date;
                            $edcDate = $pregnancy->edc;
                        @endphp
                        <tr class="hover:bg-black/[0.03] transition">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <a href="{{ route('maternal.prenatal.patient', $pregnancy->patient_id) }}" class="font-medium hover:underline" style="color: var(--ink);">
                                    {{ fullName($pregnancy->patient->last_name, $pregnancy->patient->first_name, $pregnancy->patient->middle_name, $pregnancy->patient->suffix) }}
                                </a>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap hidden md:table-cell" style="color: var(--ink-muted);">Zone {{ $pregnancy->patient->household?->zone?->zone_number ?? $pregnancy->patient->household?->zone_id ?? '-' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if (! empty($pregnancy->risk_flags))
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold" style="background: var(--amber-soft); color: var(--amber);">
                                        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> High risk
                                    </span>
                                @else
                                    <span style="color: var(--ink-subtle);">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap" style="color: var(--ink-muted);">G{{ $pregnancy->gravidity }} P{{ $pregnancy->parity }}</td>
                            <td class="px-4 py-3 whitespace-nowrap font-medium" style="color: var(--ink);">{{ $edcDate?->format('M d, Y') ?? '-' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap" style="color: var(--ink-muted);">
                                @if ($pregnancy->aog_weeks !== null)
                                    {{ $pregnancy->aog_weeks }} wk{{ $pregnancy->aog_weeks == 1 ? '' : 's' }}
                                @elseif ($edcDate !== null)
                                    {{ max(0, \Carbon\Carbon::today()->diffInWeeks(\Carbon\Carbon::parse($edcDate)->subDays(280))) }} wks
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap" style="color: var(--ink-muted);">{{ $pregnancy->visits_count }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if ($nextVisit === null)
                                    <span style="color: var(--ink-subtle);">-</span>
                                @elseif (\Carbon\Carbon::parse($nextVisit)->lt(\Carbon\Carbon::today()))
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--danger-soft); color: var(--danger);">
                                        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> Overdue {{ \Carbon\Carbon::parse($nextVisit)->format('M d') }}
                                    </span>
                                @else
                                    <span style="color: var(--ink-muted);">{{ \Carbon\Carbon::parse($nextVisit)->format('M d, Y') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <a href="{{ route('maternal.prenatal.patient', $pregnancy->patient_id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition"
                                   style="background: var(--teal-soft); color: var(--primary);">
                                    Open <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs" style="color: var(--ink-muted);">
        <span>Showing {{ $rows->count() }} of {{ $total }}</span>
        @if ($total > $rows->count())
            <a href="{{ route('maternal.prenatal.index') }}" class="font-semibold hover:underline" style="color: var(--primary);">View full register</a>
        @endif
    </div>
@endif
