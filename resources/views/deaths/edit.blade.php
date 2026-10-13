@extends('layouts.app')

@section('title', 'Edit Death Record')

@section('content')
<div class="space-y-5 lg:space-y-6">
    <div>
        <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Edit Death Record</h1>
        <p class="text-sm mt-1" style="color: var(--ink-muted);">Edit death record for {{ fullName($death->patient->last_name, $death->patient->first_name, $death->patient->middle_name) }}</p>
    </div>

    <form method="POST" action="{{ route('deaths.update', $death) }}" class="space-y-5 lg:space-y-6">
        @csrf
        @method('PUT')

        <div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border);">
            <h2 class="font-display font-semibold text-sm mb-4" style="color: var(--ink);">Death Information</h2>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="date_of_death" label="Date of Death" help="Date when the patient died." required>
                    <x-slot:control>
                        <input type="date" name="date_of_death" id="date_of_death" value="{{ old('date_of_death', $death->date_of_death->toDateString()) }}"
                               class="w-full rounded-lg border border-border px-3 py-2.5 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-accent-blue" required>
                    </x-slot:control>
                </x-field>

                <x-field name="place_of_death" label="Place of Death" help="Where the death occurred (e.g., Home, Hospital, Health Center, etc.)." required>
                    <x-slot:control>
                        <input type="text" name="place_of_death" id="place_of_death" value="{{ old('place_of_death', $death->place_of_death) }}"
                               placeholder="e.g., Home, Hospital, Health Center"
                               class="w-full rounded-lg border border-border px-3 py-2.5 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-accent-blue" required>
                    </x-slot:control>
                </x-field>
            </div>

            <div class="mt-4">
                <x-field name="cause_diagnosis_id" label="Cause of Death (ICD)" help="Select from ICD-10 diagnosis list. Leave blank if not applicable.">
                    <x-slot:control>
                        <select name="cause_diagnosis_id" id="cause_diagnosis_id" data-selected="{{ old('cause_diagnosis_id', $death->cause_diagnosis_id) }}"
                                class="w-full rounded-lg border border-border px-3 py-2.5 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-accent-blue">
                            <option value="">Select diagnosis (optional)</option>
                        </select>
                    </x-slot:control>
                </x-field>
            </div>

            <div class="mt-4">
                <x-field name="cause_text" label="Cause of Death (Free Text)" help="Additional details or cause not covered by ICD list.">
                    <x-slot:control>
                        <textarea name="cause_text" id="cause_text" rows="3" placeholder="Free-text cause of death if not in ICD list"
                                  class="w-full rounded-lg border border-border px-3 py-2.5 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-accent-blue">{{ old('cause_text', $death->cause_text) }}</textarea>
                    </x-slot:control>
                </x-field>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="maternal_death" id="maternal_death" class="rounded border-border text-primary focus:ring-primary h-4 w-4" {{ $death->maternal_death ? 'checked' : '' }}>
                    <label for="maternal_death" class="text-sm text-ink">Maternal Death</label>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="perinatal_death" id="perinatal_death" class="rounded border-border text-primary focus:ring-primary h-4 w-4" {{ $death->perinatal_death ? 'checked' : '' }}>
                    <label for="perinatal_death" class="text-sm text-ink">Perinatal Death</label>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="neonatal_death" id="neonatal_death" class="rounded border-border text-primary focus:ring-primary h-4 w-4" {{ $death->neonatal_death ? 'checked' : '' }}>
                    <label for="neonatal_death" class="text-sm text-ink">Neonatal Death</label>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="infant_death" id="infant_death" class="rounded border-border text-primary focus:ring-primary h-4 w-4" {{ $death->infant_death ? 'checked' : '' }}>
                    <label for="infant_death" class="text-sm text-ink">Infant Death</label>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="under_five_death" id="under_five_death" class="rounded border-border text-primary focus:ring-primary h-4 w-4" {{ $death->under_five_death ? 'checked' : '' }}>
                    <label for="under_five_death" class="text-sm text-ink">Under-5 Death</label>
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('deaths.show', $death) }}" class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-border text-xs lg:text-sm font-medium text-ink transition hover:bg-black/5">Cancel</a>
            <button type="submit" class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-red-600 text-white text-xs lg:text-sm font-medium transition-colors hover:bg-red-700">
                <i class="fa-solid fa-save mr-1.5" aria-hidden="true"></i> Save Changes
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const select = document.querySelector('[name="cause_diagnosis_id"]');
        if (!select) return;

        select.addEventListener('focus', function() {
            if (select.dataset.loaded) return;
            fetch('/search/diagnoses?q=')
                .then(r => r.json())
                .then(data => {
                    data.forEach(d => {
                        const opt = document.createElement('option');
                        opt.value = d.id;
                        opt.textContent = d.diagnosis_code + ' - ' + d.diagnosis_name;
                        select.appendChild(opt);
                    });
                    if (select.dataset.selected) {
                        select.value = select.dataset.selected;
                    }
                    select.dataset.loaded = 'true';
                });
        });
    });
</script>
@endpush