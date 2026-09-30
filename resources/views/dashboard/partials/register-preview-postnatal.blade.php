@php
    $total = $total ?? $rows->count();
@endphp

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
    <div>
        <h2 class="font-display font-semibold text-lg lg:text-xl" style="color: var(--ink);">Postnatal register</h2>
        <p class="text-sm mt-0.5" style="color: var(--ink-muted);">24h / 7d / 14d / 28d postpartum follow-ups.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2 shrink-0">
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--accent-soft); color: var(--accent);">{{ $total }} total</span>
        <a href="{{ route('maternal.postnatal.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold transition hover:bg-black/[0.03]"
           style="border: 1px solid var(--border); color: var(--primary);">
            Open full register <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </a>
    </div>
</div>

@if ($rows->isEmpty())
    <div class="rounded-xl border border-dashed p-10 text-center" style="background: var(--bg-surface); border-color: var(--border);">
        <i class="fa-solid fa-child-reaching text-3xl mb-3" style="color: var(--ink-subtle);" aria-hidden="true"></i>
        <p class="font-semibold" style="color: var(--ink);">No postnatal records</p>
        <p class="text-sm mt-1" style="color: var(--ink-muted);">Record a delivery to start the 24h / 7d / 14d / 28d postpartum schedule.</p>
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
                        <th class="px-4 py-3 font-semibold whitespace-nowrap">Mother</th>
                        <th class="px-4 py-3 font-semibold whitespace-nowrap hidden md:table-cell">Zone</th>
                        <th class="px-4 py-3 font-semibold whitespace-nowrap">Delivered</th>
                        <th class="px-4 py-3 font-semibold whitespace-nowrap">Outcome</th>
                        <th class="px-4 py-3 font-semibold whitespace-nowrap">Next postpartum visit</th>
                        <th class="px-4 py-3 font-semibold whitespace-nowrap text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y" style="border-color: var(--border);">
                    @foreach ($rows as $record)
                        @php
                            $slots = [
                                ['col' => 'postpartum_24h_date', 'window' => 1, 'label' => '24h'],
                                ['col' => 'postpartum_7d_date', 'window' => 7, 'label' => '7d'],
                                ['col' => 'postpartum_14d_date', 'window' => 14, 'label' => '14d'],
                                ['col' => 'postpartum_28d_date', 'window' => 28, 'label' => '28d'],
                            ];
                            $next = null;
                            foreach ($slots as $slot) {
                                if ($record->{$slot['col']} === null) {
                                    $next = $slot;
                                    break;
                                }
                            }
                            $nextDate = $next !== null ? \Carbon\Carbon::parse($record->delivery_date)->addDays($next['window']) : null;
                            $overdue = $nextDate !== null && $nextDate->lt(\Carbon\Carbon::today());
                        @endphp
                        <tr class="hover:bg-black/[0.03] transition">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <a href="{{ route('maternal.postnatal.patient', $record->patient_id) }}" class="font-medium hover:underline" style="color: var(--ink);">
                                    {{ fullName($record->patient->last_name, $record->patient->first_name, $record->patient->middle_name, $record->patient->suffix) }}
                                </a>
                                @if (! empty($record->danger_signs_mother))
                                    <span class="ml-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold" style="background: var(--amber-soft); color: var(--amber);">
                                        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> High risk
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap hidden md:table-cell" style="color: var(--ink-muted);">Zone {{ $record->patient->household?->zone?->zone_number ?? $record->patient->household?->zone_id ?? '-' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap font-medium" style="color: var(--ink);">{{ $record->delivery_date->format('M d, Y') }}</td>
                            <td class="px-4 py-3 whitespace-nowrap" style="color: var(--ink-muted);">{{ \App\Models\PostnatalRecord::OUTCOMES[$record->pregnancy_outcome] ?? $record->pregnancy_outcome }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if ($next === null)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--teal-soft); color: var(--primary);">
                                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Complete
                                    </span>
                                @elseif ($overdue)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--danger-soft); color: var(--danger);">
                                        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> {{ $next['label'] }} overdue ({{ $nextDate->format('M d') }})
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--accent-blue-soft); color: var(--accent-blue);">
                                        <i class="fa-regular fa-calendar" aria-hidden="true"></i> {{ $next['label'] }} · {{ $nextDate->format('M d') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <a href="{{ route('maternal.postnatal.patient', $record->patient_id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition"
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
            <a href="{{ route('maternal.postnatal.index') }}" class="font-semibold hover:underline" style="color: var(--primary);">View full register</a>
        @endif
    </div>
@endif
