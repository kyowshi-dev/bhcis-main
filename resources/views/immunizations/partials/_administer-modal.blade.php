@php
    $adminErrors = $errors->hasAny(['vaccine_id', 'dose_number', 'date_given', 'temp_recorded', 'child_weight_kg', 'child_height_cm', 'override_reason', 'notes', 'doses']);
    $reopenOutOfWindow = $adminErrors && ($errors->has('override_reason') || old('override_reason') !== null);
    $reopenBatchDoses = [];
    if ($adminErrors && is_array(old('doses'))) {
        $reopenBatchDoses = collect(old('doses'))->map(function ($id) use ($vaccines, $records, $reopenOutOfWindow) {
            $vaccine = $vaccines->firstWhere('id', (int) $id);

            return [
                'id' => (int) $id,
                'name' => $vaccine?->vaccine_name ?? 'Vaccine #'.$id,
                'doseNumber' => $records->where('vaccine_id', (int) $id)->count() + 1,
                'outOfWindow' => $reopenOutOfWindow,
            ];
        })->values()->all();
    }
@endphp

<div
    x-data='administerVaccine()'
    x-init="initReopen({{ $adminErrors ? 'true' : 'false' }}, @js($reopenBatchDoses)); $data.isChild = {{ ($patient->age ?? 0) < 18 ? 'true' : 'false' }}"
    @keydown.escape.window="adminOpen = false"
    x-on:open-administer.window="openAdminister($event.detail)"
>
    <template x-teleport="body">
        <div x-show="adminOpen"
             x-cloak
             x-transition.opacity.duration.200ms
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="display: none;"
             role="dialog"
             aria-modal="true"
             aria-labelledby="administerModalTitle">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="adminOpen = false"></div>
            <div x-show="adminOpen"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-2xl border shadow-lg"
                 style="background: var(--bg-surface-elevated); border-color: var(--border);">
                <form method="POST"
                      :action="mode === 'batch' ? @js(route('immunizations.administer-batch', $patient->id)) : @js(route('immunizations.administer', $patient->id))">
                    @csrf
                    <template x-if="mode === 'single'">
                        <input type="hidden" name="vaccine_id" :value="vaccineId">
                    </template>
                    <template x-if="mode === 'batch'">
                        <template x-for="dose in batchDoses" :key="dose.id">
                            <input type="hidden" name="doses[]" :value="dose.id">
                        </template>
                    </template>

                    <div class="flex items-center justify-between border-b px-6 py-4" style="border-color: var(--border);">
                        <div>
                            <h3 id="administerModalTitle" class="font-display font-semibold text-lg" style="color: var(--ink);">
                                <span x-show="mode === 'single'">Record dose</span>
                                <span x-show="mode === 'batch'">Record doses</span>
                            </h3>
                            <p class="text-sm mt-0.5" style="color: var(--ink-muted);">
                                <span x-show="mode === 'single' && vaccineName" x-text="vaccineName + (doseNumber ? ` ${doseNumber}` : '')" class="font-medium" style="color: var(--ink);"></span>
                                <span x-show="mode === 'batch'" class="flex flex-wrap gap-1.5">
                                    <template x-for="dose in batchDoses" :key="dose.id">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold" style="background: rgba(0,0,0,0.05); color: var(--ink);"
                                              x-text="(dose.name || '') + (dose.doseNumber ? ' ' + dose.doseNumber : '')"></span>
                                    </template>
                                </span>
                            </p>
                        </div>
                        <button type="button" @click="adminOpen = false" aria-label="Close" class="rounded-lg p-1.5 transition-colors hover:bg-black/5" style="color: var(--ink-muted);">
                            <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
                        </button>
                    </div>

                    <div class="p-5 space-y-4">
                        <div x-show="outOfWindow" class="rounded-xl border px-4 py-3 text-sm" style="background: var(--accent-soft); border-color: var(--amber); color: var(--amber);">
                            <i class="fa-solid fa-clock mr-1.5" aria-hidden="true"></i>
                            <span x-show="mode === 'single'">This vaccine is outside its recommended age window. An override reason is required.</span>
                            <span x-show="mode === 'batch'">One or more selected doses are outside their recommended age window. An override reason is required.</span>
                        </div>

                        <div>
                            <label for="administer_date" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">
                                Date given
                            </label>
                            <input id="administer_date" name="date_given" type="date" max="{{ now()->toDateString() }}"
                                   value="{{ old('date_given', now()->toDateString()) }}"
                                   class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                                   style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                            @error('date_given')
                                <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="administer_temp" class="mb-1 inline-flex items-center gap-1.5 text-xs font-medium" style="color: var(--ink-muted);">
                                Body temperature (°C)
                                <i class="fa-regular fa-circle-question text-[0.85rem]" role="img" aria-label="Optional. Record before administering injectable vaccines." title="Optional. Record before administering injectable vaccines." style="color: var(--ink-subtle);"></i>
                            </label>
                            <input id="administer_temp" name="temp_recorded" type="number" step="0.1" min="30" max="45"
                                   value="{{ old('temp_recorded') }}" placeholder="e.g. 36.5"
                                   class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                                   style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                            @error('temp_recorded')
                                <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="administer_weight" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">
                                    Weight (kg) <span x-show="isChild" style="color: var(--danger);">*</span>
                                </label>
                                <input id="administer_weight" name="child_weight_kg" type="number" step="0.01" min="0" max="100"
                                       value="{{ old('child_weight_kg') }}" placeholder="e.g. 6.5" :required="isChild"
                                       class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                                @error('child_weight_kg')
                                    <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="administer_height" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">
                                    Height (cm) <span x-show="isChild" style="color: var(--danger);">*</span>
                                </label>
                                <input id="administer_height" name="child_height_cm" type="number" step="0.1" min="20" max="200"
                                       value="{{ old('child_height_cm') }}" placeholder="e.g. 65" :required="isChild"
                                       class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);">
                                @error('child_height_cm')
                                    <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div x-show="outOfWindow">
                            <label for="administer_override" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">
                                Override reason <span style="color: var(--danger);">*</span>
                            </label>
                            <textarea id="administer_override" name="override_reason" rows="2" maxlength="500"
                                      :required="outOfWindow"
                                      class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                                      style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);"
                                      placeholder="e.g. Catch-up per physician advice">{{ old('override_reason') }}</textarea>
                            @error('override_reason')
                                <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="administer_notes" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Notes</label>
                            <textarea id="administer_notes" name="notes" rows="2" maxlength="500"
                                      class="w-full rounded-lg border px-3 py-2.5 text-sm focus:outline-none focus:ring-2"
                                      style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);"
                                      placeholder="Batch number, site, remarks (optional)">{{ old('notes') }}</textarea>
                            @error('notes')
                                <p class="mt-1 text-xs font-medium" style="color: var(--danger);">{{ $message }}</p>
                            @enderror
                        </div>

                        <div x-show="mode === 'batch'" class="rounded-xl border px-4 py-3 text-sm" style="background: var(--accent-blue-soft); border-color: var(--accent-blue); color: var(--ink);">
                            <i class="fa-solid fa-circle-info mr-1.5" style="color: var(--accent-blue);" aria-hidden="true"></i>
                            One date, temperature, weight, height, and notes apply to all selected doses. Each dose is still checked against its own schedule.
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-2 border-t px-6 py-4" style="border-color: var(--border);">
                        <button type="button" @click="adminOpen = false" class="rounded-lg border px-4 py-2.5 text-sm font-semibold transition-colors hover:bg-black/[0.03]" style="border-color: var(--border); color: var(--ink-muted);">
                            Cancel
                        </button>
                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90" style="background: var(--primary);">
                            <i class="fa-solid fa-syringe" aria-hidden="true"></i>
                            <span x-show="mode === 'single'">Record dose</span>
                            <span x-show="mode === 'batch'">Record <span x-text="batchDoses.length"></span> dose(s)</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
