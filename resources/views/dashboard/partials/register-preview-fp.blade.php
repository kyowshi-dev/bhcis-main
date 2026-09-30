@php
    $total = $total ?? $rows->count();
@endphp

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
    <div>
        <h2 class="font-display font-semibold text-lg lg:text-xl" style="color: var(--ink);">Family planning register</h2>
        <p class="text-sm mt-0.5" style="color: var(--ink-muted);">Active acceptors and follow-up schedule.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2 shrink-0">
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--accent-blue-soft); color: var(--accent-blue);">{{ $total }} total</span>
        <a href="{{ route('maternal.family-planning.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold transition hover:bg-black/[0.03]"
           style="border: 1px solid var(--border); color: var(--primary);">
            Open full register <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </a>
    </div>
</div>

@if ($rows->isEmpty())
    <div class="rounded-xl border border-dashed p-10 text-center" style="background: var(--bg-surface); border-color: var(--border);">
        <i class="fa-solid fa-hand-holding-heart text-3xl mb-3" style="color: var(--ink-subtle);" aria-hidden="true"></i>
        <p class="font-semibold" style="color: var(--ink);">No family planning clients yet</p>
        <p class="text-sm mt-1" style="color: var(--ink-muted);">Open a patient profile and register a client under Maternal Care.</p>
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
                        <th class="px-4 py-3 font-semibold whitespace-nowrap">Type</th>
                        <th class="px-4 py-3 font-semibold whitespace-nowrap">Method</th>
                        <th class="px-4 py-3 font-semibold whitespace-nowrap">Next follow-up</th>
                        <th class="px-4 py-3 font-semibold whitespace-nowrap text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y" style="border-color: var(--border);">
                    @foreach ($rows as $client)
                        @php
                            $overdue = $client->is_active && $client->schedule_next_visit !== null && \Carbon\Carbon::parse($client->schedule_next_visit)->lt(\Carbon\Carbon::today());
                            $dueSoon = $client->is_active && $client->schedule_next_visit !== null && ! $overdue && \Carbon\Carbon::parse($client->schedule_next_visit)->lte(\Carbon\Carbon::today()->addDays(7));
                        @endphp
                        <tr class="hover:bg-black/[0.03] transition">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <a href="{{ route('maternal.family-planning.patient', $client->patient_id) }}" class="font-medium hover:underline" style="color: var(--ink);">
                                    {{ fullName($client->patient->last_name, $client->patient->first_name, $client->patient->middle_name, $client->patient->suffix) }}
                                </a>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap hidden md:table-cell" style="color: var(--ink-muted);">Zone {{ $client->patient->household?->zone?->zone_number ?? $client->patient->household?->zone_id ?? '-' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap" style="color: var(--ink-muted);">{{ \App\Models\FamilyPlanningClient::TYPES[$client->type_of_client] ?? $client->type_of_client }}</td>
                            <td class="px-4 py-3 whitespace-nowrap font-medium" style="color: var(--ink);">{{ $client->method }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if (! $client->is_active || $client->schedule_next_visit === null)
                                    <span style="color: var(--ink-subtle);">-</span>
                                @elseif ($overdue)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--danger-soft); color: var(--danger);">
                                        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> Overdue {{ \Carbon\Carbon::parse($client->schedule_next_visit)->format('M d') }}
                                    </span>
                                @elseif ($dueSoon)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" style="background: var(--accent-blue-soft); color: var(--accent-blue);">
                                        <i class="fa-regular fa-calendar" aria-hidden="true"></i> {{ \Carbon\Carbon::parse($client->schedule_next_visit)->format('M d') }}
                                    </span>
                                @else
                                    <span style="color: var(--ink-muted);">{{ \Carbon\Carbon::parse($client->schedule_next_visit)->format('M d, Y') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right">
                                <a href="{{ route('maternal.family-planning.patient', $client->patient_id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition"
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
            <a href="{{ route('maternal.family-planning.index') }}" class="font-semibold hover:underline" style="color: var(--primary);">View full register</a>
        @endif
    </div>
@endif
