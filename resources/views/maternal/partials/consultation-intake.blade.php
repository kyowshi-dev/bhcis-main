<div class="space-y-5">
    @php($previousVitals ??= null)
    <div class="space-y-4">
        <h3 class="font-semibold pb-2 border-b border-border text-sm lg:text-base text-ink">Visit details</h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-field name="mode_of_transaction" :id="$fieldPrefix.'mode_of_transaction'" required>
                <x-slot:label>Mode of transaction</x-slot:label>
                <x-slot:control>
                    <select name="mode_of_transaction" id="{{ $fieldPrefix }}mode_of_transaction"
                            class="w-full rounded-lg border border-border px-3 py-2.5 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-accent-blue" required>
                        <option value="Walk-in" @selected(old('mode_of_transaction', 'Walk-in') === 'Walk-in')>Walk-in</option>
                        <option value="Visited" @selected(old('mode_of_transaction') === 'Visited')>Visited</option>
                        <option value="Referral" @selected(old('mode_of_transaction') === 'Referral')>Referral</option>
                    </select>
                </x-slot:control>
            </x-field>

            <x-field name="nature_of_visit" :id="$fieldPrefix.'nature_of_visit'" required>
                <x-slot:label>Nature of visit</x-slot:label>
                <x-slot:control>
                    <select name="nature_of_visit" id="{{ $fieldPrefix }}nature_of_visit"
                            class="w-full rounded-lg border border-border px-3 py-2.5 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-accent-blue" required>
                        <option value="">Select visit type...</option>
                        <option value="New Consultation/Case" @selected(old('nature_of_visit') === 'New Consultation/Case')>New Consultation/Case</option>
                        <option value="Follow-up Visit" @selected(old('nature_of_visit') === 'Follow-up Visit')>Follow-up Visit</option>
                    </select>
                </x-slot:control>
            </x-field>
        </div>

        <x-field name="chief_complaint" :id="$fieldPrefix.'chief_complaint'">
            <x-slot:label>Chief complaints</x-slot:label>
            <x-slot:control>
                <textarea name="chief_complaint" id="{{ $fieldPrefix }}chief_complaint" rows="2"
                          class="w-full rounded-lg border border-border px-3 py-2.5 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-accent-blue"
                          placeholder="e.g. Routine prenatal checkup">{{ old('chief_complaint') }}</textarea>
            </x-slot:control>
        </x-field>
    </div>

    <div class="space-y-4">
        <h3 class="font-semibold pb-2 border-b border-border text-sm lg:text-base text-ink">Vitals</h3>

        @include('consultations.partials.vitals-fields', [
            'fieldPrefix' => $fieldPrefix,
            'required' => true,
            'values' => [
                'bp_systolic' => old('bp_systolic', $previousVitals->bp_systolic ?? ''),
                'bp_diastolic' => old('bp_diastolic', $previousVitals->bp_diastolic ?? ''),
                'weight' => old('weight', $previousVitals->weight_kg ?? ''),
                'height' => old('height', $previousVitals->height_cm ?? ''),
                'temperature' => old('temperature', $previousVitals->temperature_c ?? ''),
            ],
        ])
    </div>
</div>
