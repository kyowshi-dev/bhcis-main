@extends('layouts.app')

@section('title', 'Households')

@section('content')
<div class="space-y-5 lg:space-y-6">
    <!-- Header -->
    <x-page-header title="Households" :subtitle="__('Registered households by zone for barangay census and patient mapping.')">
        <x-slot:actions>
            <x-btn href="{{ route('households.create') }}" icon="fa-solid fa-house-chimney">
                + Add household
            </x-btn>
        </x-slot:actions>
    </x-page-header>

    <x-flash />

    <!-- Stats Cards - varied layout -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Featured: Total Population -->
        <div class="lg:col-span-1 sm:col-span-2 lg:col-span-1 rounded-xl border p-5 relative overflow-hidden"
             style="background: var(--bg-surface-elevated); border-color: var(--border); box-shadow: var(--shadow-sm);">
            <i class="fa-solid fa-people-group absolute top-4 right-4 text-6xl opacity-5"
               style="color: var(--primary);" aria-hidden="true"></i>
            <p class="text-xs font-semibold uppercase tracking-wide" style="color: var(--ink-muted);">Total Population</p>
            <p class="text-3xl lg:text-4xl font-bold mt-2" style="color: var(--primary);">{{ $totalPopulation }}</p>
            <p class="text-xs mt-1" style="color: var(--ink-muted);">All residents across zones</p>
        </div>

        <!-- Total Households -->
        <div class="rounded-xl border p-4" style="background: var(--bg-surface-elevated); border-color: var(--border); box-shadow: var(--shadow-sm);">
            <div class="flex items-center gap-2 mb-2">
                <i class="fa-solid fa-house-chimney text-xl" style="color: var(--primary);" aria-hidden="true"></i>
                <p class="text-xs font-semibold uppercase tracking-wide" style="color: var(--ink-muted);">Total Households</p>
            </div>
            <p class="text-2xl lg:text-3xl font-bold" style="color: var(--ink);">{{ $totalHouseholds }}</p>
        </div>

        <!-- Infants -->
        <div class="rounded-xl border p-4" style="background: var(--bg-surface-elevated); border-color: var(--border); box-shadow: var(--shadow-sm);">
            <div class="flex items-center gap-2 mb-2">
                <i class="fa-solid fa-baby text-xl" style="color: var(--accent-blue);" aria-hidden="true"></i>
                <p class="text-xs font-semibold uppercase tracking-wide" style="color: var(--ink-muted);">Infants (0-1yr)</p>
            </div>
            <p class="text-2xl lg:text-3xl font-bold" style="color: var(--accent-blue);">{{ $vulnerableGroups['infants'] ?? 0 }}</p>
        </div>

        <!-- Seniors -->
        <div class="rounded-xl border p-4" style="background: var(--bg-surface-elevated); border-color: var(--border); box-shadow: var(--shadow-sm);">
            <div class="flex items-center gap-2 mb-2">
                <i class="fa-solid fa-person-cane text-xl" style="color: var(--amber);" aria-hidden="true"></i>
                <p class="text-xs font-semibold uppercase tracking-wide" style="color: var(--ink-muted);">Seniors (65+)</p>
            </div>
            <p class="text-2xl lg:text-3xl font-bold" style="color: var(--amber);">{{ $vulnerableGroups['seniors'] ?? 0 }}</p>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="rounded-xl border p-4" style="background: var(--bg-surface-elevated); border-color: var(--border); box-shadow: var(--shadow-sm);">
        <form method="GET" action="{{ route('households.index') }}" class="space-y-3">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Search Input -->
                <x-field name="search" label="Search" :required="false">
                    <x-slot:control>
                        <input type="text"
                               name="search"
                               placeholder="Head name, contact, or zone..."
                               value="{{ $search }}"
                               class="w-full px-3 py-2 rounded-lg border text-sm"
                               style="border-color: var(--border); background: var(--bg-surface);">
                    </x-slot:control>
                </x-field>

                <!-- Zone Filter -->
                <x-field name="zone_id" label="Zone" :required="false">
                    <x-slot:control>
                        <select name="zone_id" class="w-full px-3 py-2 rounded-lg border text-sm"
                                style="border-color: var(--border); background: var(--bg-surface);">
                            <option value="">All zones</option>
                            @foreach ($zones as $zone)
                                <option value="{{ $zone->id }}" {{ $zone_id == $zone->id ? 'selected' : '' }}>
                                    {{ $zone->zone_number }}
                                </option>
                            @endforeach
                        </select>
                    </x-slot:control>
                </x-field>

                <!-- Date From -->
                <x-field name="date_from" label="From Date" :required="false">
                    <x-slot:control>
                        <input type="date"
                               name="date_from"
                               value="{{ $date_from }}"
                               class="w-full px-3 py-2 rounded-lg border text-sm"
                               style="border-color: var(--border); background: var(--bg-surface);">
                    </x-slot:control>
                </x-field>

                <!-- Date To -->
                <x-field name="date_to" label="To Date" :required="false">
                    <x-slot:control>
                        <input type="date"
                               name="date_to"
                               value="{{ $date_to }}"
                               class="w-full px-3 py-2 rounded-lg border text-sm"
                               style="border-color: var(--border); background: var(--bg-surface);">
                    </x-slot:control>
                </x-field>
            </div>

            <div class="flex gap-2 justify-end">
                <a href="{{ route('households.index') }}"
                   class="inline-flex items-center justify-center px-3 py-2 rounded-lg text-xs font-semibold transition"
                   style="background: var(--bg-surface); border: 1px solid var(--border); color: var(--ink);">
                    Clear Filters
                </a>
                <button type="submit"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-xs font-semibold text-white transition"
                        style="background: var(--primary);">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Bulk Actions Bar -->
    <div id="bulkActionsBar" class="hidden rounded-xl border p-4 gap-3 flex items-center"
         style="background: var(--primary-soft); border-color: var(--border); box-shadow: var(--shadow-sm);">
        <div class="flex-1">
            <p class="text-sm font-semibold" style="color: var(--primary);"><span id="selectedCount">0</span> selected</p>
        </div>
        <form id="csvExportForm" method="POST" action="{{ route('households.export.csv') }}" data-no-loading style="display: inline;">
            @csrf
            <button type="button" onclick="submitBulkAction('csvExportForm')"
                    class="inline-flex items-center justify-center px-3 py-2 rounded-lg text-xs font-semibold transition"
                    style="background: var(--bg-surface); border: 1px solid var(--border); color: var(--ink);">
                Export CSV
            </button>
        </form>
        <form id="pdfExportForm" method="POST" action="{{ route('households.export.pdf') }}" data-no-loading style="display: inline;">
            @csrf
            <button type="button" onclick="submitBulkAction('pdfExportForm')"
                    class="inline-flex items-center justify-center px-3 py-2 rounded-lg text-xs font-semibold transition"
                    style="background: var(--bg-surface); border: 1px solid var(--border); color: var(--ink);">
                Export PDF
            </button>
        </form>
        <button type="button" onclick="openZoneModal()"
                class="inline-flex items-center justify-center px-3 py-2 rounded-lg text-xs font-semibold transition"
                style="background: var(--primary); color: white;">
            Reassign Zone
        </button>
        <button type="button" onclick="clearSelection()"
                class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold transition"
                style="background: var(--bg-surface); border: 1px solid var(--border); color: var(--ink);">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i> Clear
        </button>
    </div>

    <!-- Households Table -->
    <div class="rounded-2xl border overflow-hidden"
         style="background: var(--bg-surface-elevated); border-color: var(--border); box-shadow: var(--shadow-sm);">
        <div class="px-4 lg:px-5 py-3 border-b flex justify-between items-center"
             style="border-color: var(--border); background: var(--bg-surface);">
            <p class="text-xs lg:text-sm" style="color: var(--ink-muted);">
                <span class="font-semibold" style="color: var(--ink);">{{ $households->count() }}</span> households shown
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-xs lg:text-sm">
                <thead>
                    <tr style="background: var(--teal-soft);">
                        <th class="px-3 py-2.5 lg:py-3 font-semibold" style="color: var(--ink-muted); width: 40px;">
                            <input type="checkbox" id="selectAllCheckbox" class="rounded"
                                   onchange="toggleSelectAll(this)">
                        </th>
                        <th class="px-3 lg:px-4 py-2.5 lg:py-3 font-semibold uppercase tracking-wide"
                            style="color: var(--ink-muted);">
                            Zone
                        </th>
                        <th class="px-3 lg:px-4 py-2.5 lg:py-3 font-semibold uppercase tracking-wide"
                            style="color: var(--ink-muted);">
                            Head of Household
                        </th>
                        <th class="px-3 lg:px-4 py-2.5 lg:py-3 font-semibold uppercase tracking-wide"
                            style="color: var(--ink-muted);">
                            Contact number
                        </th>
                        <th class="px-3 lg:px-4 py-2.5 lg:py-3 font-semibold uppercase tracking-wide text-center"
                            style="color: var(--ink-muted);">
                            Residents
                        </th>
                        <th class="px-3 lg:px-4 py-2.5 lg:py-3 font-semibold uppercase tracking-wide"
                            style="color: var(--ink-muted);">
                            Registered at
                        </th>
                        <th class="px-3 lg:px-4 py-2.5 lg:py-3 text-right font-semibold uppercase tracking-wide"
                            style="color: var(--ink-muted);">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
@forelse ($households as $household)
                    <tr class="border-b last:border-b-0 household-row" style="border-color: var(--border);">
                        <td class="px-3 py-2.5 lg:py-3">
                            <input type="checkbox" class="household-checkbox rounded" value="{{ $household->id }}"
                                   onchange="updateBulkActionsBar()">
                        </td>
                        <td class="px-3 lg:px-4 py-2.5 lg:py-3" style="color: var(--ink);">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium"
                                  style="background: var(--primary-soft); color: var(--primary); border: 1px solid var(--border);">
                                Zone {{ $household->zone_number }}
                            </span>
                        </td>
                        <td class="px-3 lg:px-4 py-2.5 lg:py-3" style="color: var(--ink);">
                            <a href="{{ route('households.show', $household->id) }}"
                               class="font-medium hover:underline">
                                {{ fullName($household->head_last_name, $household->head_first_name, $household->head_middle_name, $household->head_suffix) }}
                            </a>
                            @if ($household->id)
                                <div class="text-xs mt-0.5" style="color: var(--ink-subtle);">HH-{{ str_pad($household->id, 4, '0', STR_PAD_LEFT) }}</div>
                            @endif
                        </td>
                        <td class="px-3 lg:px-4 py-2.5 lg:py-3" style="color: var(--ink-muted);">
                            {{ $household->contact_number ?: '&mdash;' }}
                        </td>
                        <td class="px-3 lg:px-4 py-2.5 lg:py-3 text-center" style="color: var(--ink-muted);">
                            <a href="{{ route('households.show', $household->id) }}"
                               class="inline-flex items-center justify-center px-2 py-1 rounded-full text-xs font-semibold hover:opacity-80 transition"
                               style="background: var(--primary-soft); color: var(--primary);">
                                {{ $memberCounts[$household->id] ?? 0 }}
                            </a>
                        </td>
                        <td class="px-3 lg:px-4 py-2.5 lg:py-3" style="color: var(--ink-muted);">
                            {{ $household->created_at ? \Illuminate\Support\Carbon::parse($household->created_at)->format('M d, Y') : '&mdash;' }}
                        </td>
                        <td class="px-3 lg:px-4 py-2.5 lg:py-3 text-right whitespace-nowrap space-x-1 flex justify-end items-center">
                            <a href="{{ route('households.show', $household->id) }}"
                               class="inline-flex items-center justify-center gap-1.5 px-2 py-1.5 rounded-lg text-[11px] font-semibold transition"
                               style="background: var(--accent-blue-soft); color: var(--accent-blue); border: 1px solid var(--border);"
                               title="View household">
                                <i class="fa-solid fa-eye" aria-hidden="true"></i> View
                            </a>
                            <a href="{{ route('households.edit', $household->id) }}"
                               class="inline-flex items-center justify-center gap-1.5 px-2 py-1.5 rounded-lg text-[11px] font-semibold transition"
                               style="background: var(--primary-soft); color: var(--primary); border: 1px solid var(--border);"
                               title="Edit household">
                                <i class="fa-solid fa-pen" aria-hidden="true"></i> Edit
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center">
                            <div class="flex justify-center mb-3"><i class="fa-solid fa-house text-3xl" style="color: var(--ink-subtle);"></i></div>
                            <p class="text-sm font-medium" style="color: var(--ink);">No households found</p>
                            <p class="text-xs mt-1 mb-3" style="color: var(--ink-muted);">Start by registering a household to map your barangay population</p>
                            <a href="{{ route('households.create') }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold text-white transition hover:opacity-90" style="background: var(--primary);"><i class="fa-solid fa-plus"></i> Add household</a>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 lg:px-5 py-3 border-t" style="border-color: var(--border); background: var(--bg-surface);">
            <x-pagination :paginator="$households" />
        </div>
    </div>
</div>

<!-- Zone Reassignment Modal (using x-modal) -->
<x-modal name="zoneReassignModal" title="Reassign Zone" maxWidth="max-w-md"
         x-on:open-zone-reassign.window="open = true"
         x-on:close.window="open = false">
    <form id="zoneReassignForm" method="POST" action="{{ route('households.update-zone') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-semibold mb-2" style="color: var(--ink-muted);">Select New Zone</label>
            <select name="new_zone_id" required
                    class="w-full px-3 py-2 rounded-lg border text-sm"
                    style="border-color: var(--border); background: var(--bg-surface);">
                <option value="">--- Select Zone ---</option>
                @foreach ($zones as $zone)
                    <option value="{{ $zone->id }}">Zone {{ $zone->zone_number }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2 justify-end pt-4 border-t" style="border-color: var(--border);">
            <button type="button" @click="open = false"
                    class="px-4 py-2 rounded-lg text-sm font-semibold transition"
                    style="background: var(--bg-surface); border: 1px solid var(--border); color: var(--ink);">
                Cancel
            </button>
            <button type="submit"
                    class="px-4 py-2 rounded-lg text-sm font-semibold text-white transition"
                    style="background: var(--primary);">
                Reassign
            </button>
        </div>
    </form>
</x-modal>

<script>
function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.household-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    updateBulkActionsBar();
}

function updateBulkActionsBar() {
    const checkboxes = document.querySelectorAll('.household-checkbox:checked');
    const count = checkboxes.length;
    const bar = document.getElementById('bulkActionsBar');
    document.getElementById('selectedCount').textContent = count;

    if (count > 0) {
        bar.classList.remove('hidden');
    } else {
        bar.classList.add('hidden');
    }
}

function clearSelection() {
    document.getElementById('selectAllCheckbox').checked = false;
    document.querySelectorAll('.household-checkbox').forEach(cb => cb.checked = false);
    updateBulkActionsBar();
}

function submitBulkAction(formId) {
    const checkboxes = document.querySelectorAll('.household-checkbox:checked');
    if (checkboxes.length === 0) {
        Swal.fire({title: 'No selection', text: 'Please select at least one household.', icon: 'warning', confirmButtonColor: 'var(--primary)'});
        return;
    }

    const form = document.getElementById(formId);
    form.querySelectorAll('input[name*="household_ids"]').forEach(el => el.remove());

    checkboxes.forEach(cb => {
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'household_ids[]';
        hiddenInput.value = cb.value;
        form.appendChild(hiddenInput);
    });

    form.submit();
}

function openZoneModal() {
    const checkboxes = document.querySelectorAll('.household-checkbox:checked');
    if (checkboxes.length === 0) {
        Swal.fire({title: 'No selection', text: 'Please select at least one household.', icon: 'warning', confirmButtonColor: 'var(--primary)'});
        return;
    }

    const form = document.getElementById('zoneReassignForm');
    form.querySelectorAll('input[name="household_ids[]"]').forEach(el => el.remove());

    const ids = Array.from(checkboxes).map(cb => cb.value);
    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'household_ids[]';
        input.value = id;
        form.appendChild(input);
    });

    window.dispatchEvent(new CustomEvent('open-zone-reassign'));
}
</script>
@endsection