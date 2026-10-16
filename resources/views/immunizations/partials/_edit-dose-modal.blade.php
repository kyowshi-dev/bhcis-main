<!-- Edit Immunization Dose Modal -->
<x-modal name="editDoseModal" title="Edit Immunization Dose" maxWidth="max-w-md"
         x-on:open-edit-dose.window="open = true; loadDose($event.detail)"
         x-on:close.window="open = false">
    <div x-data="editDoseModal()" x-init="init({{ $patientId }})">
        <form @submit.prevent="submitForm()" class="space-y-4">
            <input type="hidden" name="_method" value="PUT">

            <div>
                <label class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Date Given</label>
                <input type="date"
                       x-model="form.date_given"
                       max="today"
                       class="w-full rounded-lg border border-border focus:outline-none focus:ring-2 focus:ring-accent-blue text-ink"
                       :class="{ 'border-danger': errors.date_given }">
                <p class="mt-1 text-xs text-danger" x-show="errors.date_given" x-text="errors.date_given"></p>
            </div>

            <div>
                <label class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Temperature (°C)</label>
                <input type="number" step="0.1" min="30" max="45"
                       x-model="form.temp_recorded"
                       class="w-full rounded-lg border border-border focus:outline-none focus:ring-2 focus:ring-accent-blue text-ink"
                       :class="{ 'border-danger': errors.temp_recorded }">
                <p class="mt-1 text-xs text-danger" x-show="errors.temp_recorded" x-text="errors.temp_recorded"></p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Weight (kg)</label>
                    <input type="number" step="0.1" min="0" max="100"
                           x-model="form.child_weight_kg"
                           class="w-full rounded-lg border border-border focus:outline-none focus:ring-2 focus:ring-accent-blue text-ink"
                           :class="{ 'border-danger': errors.child_weight_kg }">
                    <p class="mt-1 text-xs text-danger" x-show="errors.child_weight_kg" x-text="errors.child_weight_kg"></p>
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Height (cm)</label>
                    <input type="number" step="0.1" min="20" max="200"
                           x-model="form.child_height_cm"
                           class="w-full rounded-lg border border-border focus:outline-none focus:ring-2 focus:ring-accent-blue text-ink"
                           :class="{ 'border-danger': errors.child_height_cm }">
                    <p class="mt-1 text-xs text-danger" x-show="errors.child_height_cm" x-text="errors.child_height_cm"></p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Notes</label>
                <textarea x-model="form.notes"
                          rows="3"
                          class="w-full rounded-lg border border-border focus:outline-none focus:ring-2 focus:ring-accent-blue text-ink"
                          :class="{ 'border-danger': errors.notes }"
                          placeholder="Optional notes"></textarea>
                <p class="mt-1 text-xs text-danger" x-show="errors.notes" x-text="errors.notes"></p>
            </div>

            <div class="flex gap-2 justify-end pt-4 border-t" style="border-color: var(--border);">
                <button type="button"
                        @click="open = false"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-semibold transition"
                        style="background: var(--bg-surface); border: 1px solid var(--border); color: var(--ink);">
                    Cancel
                </button>
                <button type="submit"
                        :disabled="loading"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-lg text-sm font-semibold text-white transition"
                        style="background: var(--primary);">
                    <span x-show="loading">Saving...</span>
                    <span x-show="!loading">Save Changes</span>
                </button>
            </div>
        </form>
    </div>
</x-modal>

<script>
function editDoseModal() {
    return {
        open: false,
        patientId: null,
        doseId: null,
        vaccineId: null,
        doseNumber: null,
        loading: false,
        form: {
            date_given: '',
            temp_recorded: '',
            child_weight_kg: '',
            child_height_cm: '',
            notes: '',
        },
        errors: {},

        init(patientId) {
            this.patientId = patientId;
        },

        loadDose(detail) {
            this.doseId = detail.doseId;
            this.vaccineId = detail.vaccineId;
            this.doseNumber = detail.doseNumber;
            this.form.date_given = detail.dateGiven;
            this.form.temp_recorded = detail.tempRecorded ?? '';
            this.form.child_weight_kg = detail.childWeightKg ?? '';
            this.form.child_height_cm = detail.childHeightCm ?? '';
            this.form.notes = detail.notes ?? '';
            this.errors = {};
            this.open = true;
        },

        submitForm() {
            this.loading = true;
            this.errors = {};

            const url = `/patients/${this.patientId}/immunizations/${this.doseId}`;

            fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(this.form),
            })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => { throw err; });
                    }
                    return response.json();
                })
                .then(data => {
                    this.loading = false;
                    this.open = false;
                    window.location.reload();
                })
                .catch(err => {
                    this.loading = false;
                    if (err.errors) {
                        this.errors = err.errors;
                    } else {
                        console.error('Edit dose failed:', err);
                        Swal.fire({
                            title: 'Error',
                            text: 'Failed to update dose. Please try again.',
                            icon: 'error',
                        });
                    }
                });
        },
    };
}
</script>