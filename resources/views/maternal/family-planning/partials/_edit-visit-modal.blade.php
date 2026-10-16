<!-- Edit FP Visit Modal -->
<x-modal name="editFpVisitModal" title="Edit Follow-up Visit" maxWidth="max-w-xl"
         x-on:open-edit-fp-visit.window="open = true; loadVisit($event.detail)"
         x-on:close.window="open = false">
    <div x-data="editFpVisitModal()">
        <form @submit.prevent="submitForm()" class="space-y-4">
            <input type="hidden" name="_method" value="PUT">

            <div>
                <label for="visit_date" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Visit Date <span class="text-danger">*</span></label>
                <input type="date" id="visit_date" name="visit_date" x-model="form.visit_date" max="today"
                       class="w-full rounded-lg border border-border focus:outline-none focus:ring-2 focus:ring-accent-blue text-ink"
                       :class="{ 'border-danger': errors.visit_date }"
                       required>
                <p class="mt-1 text-xs text-danger" x-show="errors.visit_date" x-text="errors.visit_date"></p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="mode_of_transaction" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Mode of Transaction <span class="text-danger">*</span></label>
                    <select id="mode_of_transaction" name="mode_of_transaction" x-model="form.mode_of_transaction"
                            class="w-full rounded-lg border border-border focus:outline-none focus:ring-2 focus:ring-accent-blue text-ink"
                            :class="{ 'border-danger': errors.mode_of_transaction }"
                            required>
                        <option value="Walk-in">Walk-in</option>
                        <option value="Visited">Visited</option>
                        <option value="Referral">Referral</option>
                    </select>
                    <p class="mt-1 text-xs text-danger" x-show="errors.mode_of_transaction" x-text="errors.mode_of_transaction"></p>
                </div>
                <div>
                    <label for="nature_of_visit" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Nature of Visit <span class="text-danger">*</span></label>
                    <select id="nature_of_visit" name="nature_of_visit" x-model="form.nature_of_visit"
                            class="w-full rounded-lg border border-border focus:outline-none focus:ring-2 focus:ring-accent-blue text-ink"
                            :class="{ 'border-danger': errors.nature_of_visit }"
                            required>
                        <option value="New Consultation/Case">New Consultation/Case</option>
                        <option value="Follow-up Visit">Follow-up Visit</option>
                    </select>
                    <p class="mt-1 text-xs text-danger" x-show="errors.nature_of_visit" x-text="errors.nature_of_visit"></p>
                </div>
            </div>

            <div>
                <label for="chief_complaint" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Chief Complaint</label>
                <textarea id="chief_complaint" name="chief_complaint" x-model="form.chief_complaint"
                          rows="2"
                          class="w-full rounded-lg border border-border focus:outline-none focus:ring-2 focus:ring-accent-blue text-ink"
                          :class="{ 'border-danger': errors.chief_complaint }"
                          placeholder="Optional"></textarea>
                <p class="mt-1 text-xs text-danger" x-show="errors.chief_complaint" x-text="errors.chief_complaint"></p>
            </div>

            <div>
                <label for="method" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Method <span class="text-danger">*</span></label>
                <select id="method" name="method" x-model="form.method"
                        class="w-full rounded-lg border border-border focus:outline-none focus:ring-2 focus:ring-accent-blue text-ink"
                        :class="{ 'border-danger': errors.method }"
                        required>
                    @foreach (\App\Models\FamilyPlanningClient::METHODS as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-danger" x-show="errors.method" x-text="errors.method"></p>
            </div>

            <div>
                <label for="schedule_next_visit" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Next Follow-up Date</label>
                <input type="date" id="schedule_next_visit" name="schedule_next_visit" x-model="form.schedule_next_visit"
                       min="tomorrow"
                       class="w-full rounded-lg border border-border focus:outline-none focus:ring-2 focus:ring-accent-blue text-ink"
                       :class="{ 'border-danger': errors.schedule_next_visit }">
                <p class="mt-1 text-xs text-danger" x-show="errors.schedule_next_visit" x-text="errors.schedule_next_visit"></p>
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
function editFpVisitModal() {
    return {
        open: false,
        visitId: null,
        loading: false,
        form: {
            visit_date: '',
            mode_of_transaction: '',
            nature_of_visit: '',
            chief_complaint: '',
            method: '',
            schedule_next_visit: '',
        },
        errors: {},

        loadVisit(detail) {
            this.visitId = detail.visitId;
            this.form.visit_date = detail.visitDate;
            this.form.mode_of_transaction = detail.modeOfTransaction;
            this.form.nature_of_visit = detail.natureOfVisit;
            this.form.chief_complaint = detail.chiefComplaint;
            this.form.method = detail.method;
            this.form.schedule_next_visit = detail.scheduleNextVisit;
            this.errors = {};
            this.open = true;
        },

        submitForm() {
            this.loading = true;
            this.errors = {};

            const url = `/maternal/family-planning/visits/${this.visitId}`;

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
                        console.error('Edit FP visit failed:', err);
                        Swal.fire({
                            title: 'Error',
                            text: 'Failed to update visit. Please try again.',
                            icon: 'error',
                        });
                    }
                });
        },
    };
}
</script>