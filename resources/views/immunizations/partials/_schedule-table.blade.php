@props([
    'patient' => null,
    'schedule' => collect(),
    'statuses' => [],
    'eligibility' => [],
    'schedulesByVaccine' => [],
    'noShowEvents' => [],
    'records' => collect(),
])
@php
    $recordsByVaccine = $records->groupBy('vaccine_id');

    $rows = [];
    $groupPositions = [];
    foreach ($schedule as $item) {
        $groupKey = $item->vaccine->group_key;
        if ($groupKey === null) {
            $rows[] = ['key' => (string) $item->vaccine->id, 'group' => null, 'variants' => [$item]];

            continue;
        }
        if (isset($groupPositions[$groupKey])) {
            $rows[$groupPositions[$groupKey]]['variants'][] = $item;

            continue;
        }
        $groupPositions[$groupKey] = count($rows);
        $rows[] = ['key' => $groupKey, 'group' => $groupKey, 'variants' => [$item]];
    }

    $config = [
        'elig' => [],
        'names' => [],
        'doseNumbers' => [],
        'rowKeys' => [],
        'groupVariants' => [],
        'groupDefaults' => [],
    ];

    foreach ($rows as $rowIndex => $row) {
        $config['rowKeys'][] = $row['key'];
        $views = [];

        foreach ($row['variants'] as $item) {
            $vaccineId = $item->vaccine->id;
            $status = $statuses[$vaccineId] ?? 'waiting';
            $elig = $eligibility[$vaccineId] ?? ['state' => 'waiting', 'earliest_date' => null, 'requires_override' => false];
            $recordsForVaccine = $recordsByVaccine->get($vaccineId, collect());
            $givenCount = $recordsForVaccine->count();
            $nextDose = $givenCount + 1;
            $nextSchedule = $schedulesByVaccine[$vaccineId] ?? collect();
            $nextScheduleRow = $nextSchedule->where('dose_number', $nextDose)->first();

            $views[] = [
                'item' => $item,
                'vaccineId' => $vaccineId,
                'status' => $status,
                'elig' => $elig,
                'requiresTemp' => (bool) ($nextScheduleRow->requires_temp ?? false),
                'noShowEvent' => $noShowEvents[$vaccineId] ?? null,
                'earliestDate' => $elig['earliest_date'] ?? null,
                'dosesGiven' => $recordsForVaccine->sortBy('dose_number'),
                'nextDose' => $nextDose,
            ];

            $config['elig'][(string) $vaccineId] = $elig['state'];
            $config['names'][(string) $vaccineId] = $item->vaccine->vaccine_name;
            $config['doseNumbers'][(string) $vaccineId] = $nextDose;
        }

        if (count($views) > 1) {
            $views = collect($views)
                ->sortByDesc(fn (array $v) => ($v['dosesGiven']->isNotEmpty() ? 10000 : 0)
                    + ($v['elig']['state'] !== 'too_early' ? 100 : 0)
                    + (int) ($v['item']->vaccine->start_after_days ?? 0))
                ->values()
                ->all();

            $config['groupVariants'][$row['key']] = array_map(fn (array $v) => (string) $v['vaccineId'], $views);
            $config['groupDefaults'][$row['key']] = (string) $views[0]['vaccineId'];
        }

        $rows[$rowIndex]['views'] = $views;
    }

    // Presentation-only grouping: actionable rows first, then upcoming, then
    // completed. Domain statuses are untouched - this only orders the list.
    $sections = [
        'now' => ['label' => 'Ready to give now', 'rows' => []],
        'soon' => ['label' => 'Coming up', 'rows' => []],
        'done' => ['label' => 'Complete', 'rows' => []],
    ];
    foreach ($rows as $row) {
        $views = collect($row['views']);
        $actionable = $views->contains(fn (array $v) => $v['status'] !== 'completed' && ($v['elig']['state'] ?? '') !== 'too_early');
        $pending = $views->contains(fn (array $v) => $v['status'] !== 'completed');

        $sections[$actionable ? 'now' : ($pending ? 'soon' : 'done')]['rows'][] = $row;
    }
@endphp
<div x-data="scheduleTable(@js($config))">
    <h2 class="font-display font-semibold text-lg mb-1" style="color: var(--ink);">Immunization schedule</h2>
    <p class="text-sm mb-3" style="color: var(--ink-muted);">Give what is ready now, see what is coming up, and review the doses already given.</p>

    <div x-show="selectedKeys.length > 0" x-cloak class="mb-3 flex flex-wrap items-center justify-between gap-2 rounded-xl border px-4 py-2.5" style="display: none; background: var(--accent-blue-soft); border-color: var(--accent-blue);">
        <p class="text-sm font-medium" style="color: var(--ink);">
            <span x-text="selectedKeys.length"></span> dose(s) selected
            <span class="text-xs font-normal" style="color: var(--ink-muted);"> - one date, temperature, and measurement apply to the whole batch.</span>
        </p>
        <div class="flex items-center gap-2">
            <button type="button" @click="selectedKeys = []" class="px-2 py-1.5 text-xs font-semibold transition hover:bg-black/[0.03] rounded-lg" style="color: var(--ink-muted);">
                Clear
            </button>
            <button type="button" @click="openBatch()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white transition hover:shadow-md" style="background: var(--primary);">
                <i class="fa-solid fa-syringe" aria-hidden="true"></i> Administer selected
            </button>
        </div>
    </div>

    <div class="rounded-xl border overflow-hidden" style="background: var(--bg-surface-elevated); border-color: var(--border);">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead style="background: var(--teal-soft);">
                    <tr>
                        <th class="px-3 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap" style="color: var(--ink-muted);">
                            <input type="checkbox"
                                   @change="toggleAll()"
                                   :checked="allSelected"
                                   x-effect="$el.indeterminate = someSelected && !allSelected"
                                   :disabled="eligibleKeys.length === 0"
                                   aria-label="Select all due doses"
                                   class="h-4 w-4 rounded"
                                   style="accent-color: var(--primary);">
                        </th>
                        <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap" style="color: var(--ink-muted);">Vaccine</th>
                        <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap" style="color: var(--ink-muted);">Status</th>
                        <th class="px-3 lg:px-4 py-2 lg:py-3 text-left text-xs font-medium whitespace-nowrap hidden md:table-cell" style="color: var(--ink-muted);">Doses given</th>
                        <th class="px-3 lg:px-4 py-2 lg:py-3 text-right text-xs font-medium whitespace-nowrap" style="color: var(--ink-muted);">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--border)]">
                    @if (count($rows) === 0)
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-sm" style="color: var(--ink-muted);">No vaccines in schedule for this age group.</td>
                        </tr>
                    @else
                        @foreach ($sections as $section)
                            @if (count($section['rows']) === 0)
                                @continue
                            @endif
                            <tr class="bg-black/[0.03]">
                                <td colspan="5" class="px-3 lg:px-4 py-2 text-xs font-semibold" style="color: var(--ink);">
                                    {{ $section['label'] }}
                                    <span class="font-normal" style="color: var(--ink-muted);">{{ count($section['rows']) }}</span>
                                </td>
                            </tr>
                            @foreach ($section['rows'] as $row)
                                @php
                                    $views = $row['views'];
                                    $rowKey = $row['key'];
                                    $isGroupedRow = $row['group'] !== null && count($views) > 1;
                                    $firstItem = $views[0]['item'] ?? null;
                                    $rowLabel = ($isGroupedRow
                                        ? ($firstItem?->vaccine?->group_label ?? $firstItem?->vaccine?->vaccine_name)
                                        : $firstItem?->vaccine?->vaccine_name) ?? '';
                                @endphp
                                <tr class="transition-colors hover:bg-black/[0.02]">
                                    <td class="px-3 py-3 align-top">
                                        <input type="checkbox"
                                               x-model="selectedKeys"
                                               value="{{ $rowKey }}"
                                               :disabled="! isEligible(@js($rowKey))"
                                               :title="isEligible(@js($rowKey)) ? 'Include in batch dosing' : 'Only due or out-of-window doses can be selected'"
                                               aria-label="Select {{ $rowLabel }} for batch dosing"
                                               class="mt-1 h-4 w-4 rounded"
                                               style="accent-color: var(--primary);">
                                    </td>
                                    <td class="px-3 lg:px-4 py-3" style="color: var(--ink);">
                                        <div class="font-medium">{{ $rowLabel }}</div>
                                        @if ($isGroupedRow)
                                            <label class="sr-only" for="variant_{{ $rowKey }}">{{ $rowLabel }} variant</label>
                                            <select id="variant_{{ $rowKey }}"
                                                    @change="onVariantChange(@js($rowKey), $event)"
                                                    class="mt-1.5 w-full max-w-[16rem] rounded-lg border px-2 py-1.5 text-xs font-medium focus:outline-none focus:ring-2"
                                                    style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                                                @foreach ($views as $view)
                                                    <option value="{{ $view['vaccineId'] }}">{{ $view['item']->vaccine->vaccine_name }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </td>
                                    <td class="px-3 lg:px-4 py-3 align-top">
                                        @foreach ($views as $view)
                                            @php
                                                $status = $view['status'];
                                                $earliestDate = $view['earliestDate'];
                                                $noShowEvent = $view['noShowEvent'];
                                            @endphp
                                            <div @if (! $loop->first || $isGroupedRow) x-show="isActive(@js($rowKey), {{ $view['vaccineId'] }})" @endif @if (! $loop->first) style="display: none;" @endif>
                                                @if ($status === 'completed')
                                                    <x-badge state="complete" />
                                                @elseif ($status === 'no_show')
                                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                                        <x-badge state="no_show" />
                                                        @if ($noShowEvent)
                                                            <span class="text-xs" style="color: var(--ink-muted);">Missed {{ $noShowEvent->event_date->format('M d, Y') }}</span>
                                                        @endif
                                                    </div>
                                                @elseif ($status === 'out_of_window')
                                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                                        <x-badge state="out_of_window" />
                                                        @if ($earliestDate !== null)
                                                            <span class="text-xs" style="color: var(--ink-muted);">Due {{ $earliestDate->format('M d, Y') }}</span>
                                                        @endif
                                                    </div>
                                                @elseif ($status === 'overdue')
                                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                                        <x-badge state="overdue" />
                                                        @if ($earliestDate !== null)
                                                            <span class="text-xs" style="color: var(--ink-muted);">Due {{ $earliestDate->format('M d, Y') }}</span>
                                                        @endif
                                                    </div>
                                                @else
                                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                                        <x-badge state="waiting" />
                                                        @if ($earliestDate !== null)
                                                            <span class="text-xs" style="color: var(--ink-muted);">Earliest {{ $earliestDate->format('M d, Y') }}</span>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </td>
                                    <td class="px-3 lg:px-4 py-3 hidden md:table-cell align-top">
                                        @foreach ($views as $view)
                                            <div @if (! $loop->first || $isGroupedRow) x-show="isActive(@js($rowKey), {{ $view['vaccineId'] }})" @endif @if (! $loop->first) style="display: none;" @endif>
                                                <div class="flex flex-wrap gap-1.5 items-center">
                                                    @forelse ($view['dosesGiven'] as $dose)
                                                        <div class="inline-flex items-center gap-1">
                                                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium whitespace-nowrap"
                                                                  style="background: rgba(0,0,0,0.05); color: var(--ink);">
                                                                {{ $dose->dose_number }} · {{ \Carbon\Carbon::parse($dose->date_given)->format('M j') }}
                                                                @if ($dose->temp_recorded !== null)
                                                                    · {{ number_format((float) $dose->temp_recorded, 1) }}°
                                                                @endif
                                                            </span>
                                                            <button type="button"
                                                                    @click="$dispatch('open-edit-dose', {
                                                                        doseId: {{ $dose->id }},
                                                                        vaccineId: {{ $vaccineId }},
                                                                        doseNumber: {{ $dose->dose_number }},
                                                                        dateGiven: @js(\Carbon\Carbon::parse($dose->date_given)->format('Y-m-d')),
                                                                        tempRecorded: @js($dose->temp_recorded ? (float)$dose->temp_recorded : null),
                                                                        childWeightKg: @js($dose->child_weight_kg ? (float)$dose->child_weight_kg : null),
                                                                        childHeightCm: @js($dose->child_height_cm ? (float)$dose->child_height_cm : null),
                                                                        notes: @js($dose->notes ?? '')
                                                                    })"
                                                                    class="inline-flex h-6 w-6 items-center justify-center rounded transition hover:bg-black/[0.05]"
                                                                    aria-label="Edit dose {{ $dose->dose_number }}"
                                                                    style="color: var(--ink-muted);">
                                                                <i class="fa-solid fa-pen text-[10px]" aria-hidden="true"></i>
                                                            </button>
                                                        </div>
                                                    @empty
                                                        <span class="text-xs" style="color: var(--ink-subtle);">None yet</span>
                                                    @endforelse
                                                </div>
                                            </div>
                                        @endforeach
                                    </td>
                                    <td class="px-3 lg:px-4 py-3 text-right whitespace-nowrap align-top">
                                        @foreach ($views as $view)
                                            @php
                                                $vaccineId = $view['vaccineId'];
                                                $status = $view['status'];
                                                $elig = $view['elig'];
                                                $nextDose = $view['nextDose'];
                                                $noShowEvent = $view['noShowEvent'];
                                            @endphp
                                            <div @if (! $loop->first || $isGroupedRow) x-show="isActive(@js($rowKey), {{ $vaccineId }})" @endif @if (! $loop->first) style="display: none;" @endif>
                                                @if ($status === 'completed')
                                                    <span class="text-xs font-medium" style="color: var(--ink-muted);">Series complete</span>
                                                @elseif ($elig['state'] === 'too_early')
                                                    <span class="text-xs font-medium" style="color: var(--ink-muted);">
                                                        Not yet due
                                                    </span>
                                                @else
                                                    <div class="flex items-center justify-end gap-1.5">
                                                        <button type="button"
                                                            @click="$dispatch('open-administer', {
                                                                vaccineId: {{ $vaccineId }},
                                                                vaccineName: @js($view['item']->vaccine->vaccine_name),
                                                                doseNumber: {{ $nextDose }},
                                                                outOfWindow: {{ ($elig['state'] ?? '') === 'out_of_window' ? 'true' : 'false' }}
                                                            })"
                                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white transition hover:shadow-md"
                                                            style="background: var(--primary);">
                                                            <i class="fa-solid fa-syringe" aria-hidden="true"></i> Administer
                                                        </button>
                                                        <div x-data="{ menuOpen: false }" class="relative inline-block">
                                                            <button type="button"
                                                                    @click="menuOpen = !menuOpen"
                                                                    @click.away="menuOpen = false"
                                                                    @keydown.escape.window="menuOpen = false"
                                                                    :aria-expanded="menuOpen ? 'true' : 'false'"
                                                                    aria-haspopup="true"
                                                                    aria-label="More actions for {{ $view['item']->vaccine->vaccine_name }}"
                                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border transition hover:bg-black/[0.03]"
                                                                    style="border-color: var(--border); color: var(--ink-muted);">
                                                                <i class="fa-solid fa-ellipsis-vertical text-xs" aria-hidden="true"></i>
                                                            </button>
                                                            <div x-show="menuOpen" x-cloak
                                                                 class="absolute right-0 z-20 mt-1 w-52 rounded-lg border py-1 text-left shadow-lg"
                                                                 style="display: none; background: var(--bg-surface-elevated); border-color: var(--border);"
                                                                 role="menu">
                                                                @if ($status === 'no_show' && $noShowEvent)
                                                                    <form method="POST" action="{{ route('immunizations.no-show') }}" @submit.prevent="confirmClearNoShow($event.target); menuOpen = false;">
                                                                        @csrf
                                                                        <input type="hidden" name="no_show" value="0">
                                                                        <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                                                                        <input type="hidden" name="vaccine_id" value="{{ $vaccineId }}">
                                                                        <button type="submit" role="menuitem" class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs font-medium transition hover:bg-black/[0.04]" style="color: var(--ink);">
                                                                            <i class="fa-solid fa-rotate-left w-4 text-center" aria-hidden="true"></i> Clear no-show
                                                                        </button>
                                                                    </form>
                                                                @endif
                                                                @if (in_array($status, ['overdue', 'out_of_window'], true))
                                                                    <form method="POST" action="{{ route('immunizations.no-show') }}" @submit.prevent="confirmNoShow($event.target); menuOpen = false;">
                                                                        @csrf
                                                                        <input type="hidden" name="no_show" value="1">
                                                                        <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                                                                        <input type="hidden" name="vaccine_id" value="{{ $vaccineId }}">
                                                                        <input type="hidden" name="dose_number" value="{{ $nextDose }}">
                                                                        <button type="submit" role="menuitem" class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs font-medium transition hover:bg-black/[0.04]" style="color: var(--ink);">
                                                                            <i class="fa-solid fa-user-clock w-4 text-center" aria-hidden="true"></i> Mark no-show
                                                                        </button>
                                                                    </form>
                                                                @endif
                                                                @if (($elig['state'] ?? '') !== 'out_of_window')
                                                                    <form method="POST" action="{{ route('immunizations.mark-done', [$patient->id, $vaccineId]) }}" @submit.prevent="confirmMarkDone($event.target); menuOpen = false;">
                                                                        @csrf
                                                                        <button type="submit" role="menuitem" title="For doses given at another facility" class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs font-medium transition hover:bg-black/[0.04]" style="color: var(--ink);">
                                                                            <i class="fa-solid fa-arrow-right-to-bracket w-4 text-center" aria-hidden="true"></i> Mark done elsewhere
                                                                        </button>
                                                                    </form>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
