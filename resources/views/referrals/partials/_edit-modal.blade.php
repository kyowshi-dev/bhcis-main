<!-- Edit Referral Modal -->
<x-modal name="editReferralModal" title="Edit Referral" maxWidth="max-w-xl"
         x-on:open-edit-referral.window="open = true; loadReferral($event.detail)"
         x-on:close.window="open = false">
    <div x-data="editReferralModal()" x-init="init()">
        <form @submit.prevent="submitForm()" class="space-y-4">
            <input type="hidden" name="_method" value="PUT">

            <div>
                <label for="referred_to" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Destination Facility <span class="text-danger">*</span></label>
                <input type="text" id="referred_to" name="referred_to" x-model="form.referred_to"
                       class="w-full rounded-lg border border-border focus:outline-none focus:ring-2 focus:ring-accent-blue text-ink"
                       :class="{ 'border-danger': errors.referred_to }"
                       required>
                <p class="mt-1 text-xs text-danger" x-show="errors.referred_to" x-text="errors.referred_to"></p>
            </div>

            <div>
                <label class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Referral Reasons</label>
                <div class="space-y-2">
                    @foreach (App\Services\ReferralService::REASON_LABELS as $key => $label)
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="referral_reasons[]" value="{{ $key }}"
                                   x-model="form.referral_reasons"
                                   class="rounded border-border focus:ring-accent-blue text-primary">
                            <span class="text-sm" style="color: var(--ink);">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label for="referral_reason_details" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Reason Details</label>
                <textarea id="referral_reason_details" name="referral_reason_details" x-model="form.referral_reason_details"
                          rows="2"
                          class="w-full rounded-lg border border-border focus:outline-none focus:ring-2 focus:ring-accent-blue text-ink"
                          placeholder="Additional details about referral reasons"></textarea>
            </div>

            <div>
                <label for="pertinent_history" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Pertinent History <span class="text-danger">*</span></label>
                <textarea id="pertinent_history" name="pertinent_history" x-model="form.pertinent_history"
                          rows="3"
                          class="w-full rounded-lg border border-border focus:outline-none focus:ring-2 focus:ring-accent-blue text-ink"
                          :class="{ 'border-danger': errors.pertinent_history }"
                          required></textarea>
                <p class="mt-1 text-xs text-danger" x-show="errors.pertinent_history" x-text="errors.pertinent_history"></p>
            </div>

            <div>
                <label for="actions_taken" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Actions Taken</label>
                <textarea id="actions_taken" name="actions_taken" x-model="form.actions_taken"
                          rows="3"
                          class="w-full rounded-lg border border-border focus:outline-none focus:ring-2 focus:ring-accent-blue text-ink"
                          :class="{ 'border-danger': errors.actions_taken }"
                          placeholder="Actions taken at the referring facility"></textarea>
                <p class="mt-1 text-xs text-danger" x-show="errors.actions_taken" x-text="errors.actions_taken"></p>
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
function editReferralModal() {
    return {
        open: false,
        referralId: null,
        loading: false,
        form: {
            referred_to: '',
            referral_reasons: [],
            referral_reason_details: '',
            pertinent_history: '',
            actions_taken: '',
        },
        errors: {},

        init() {},

        loadReferral(detail) {
            this.referralId = detail.id;
            this.form.referred_to = detail.referredTo;
            this.form.pertinent_history = detail.pertinentHistory;
            this.form.actions_taken = detail.actionsTaken;
            this.form.referral_reasons = detail.referralReasons || [];
            this.form.referral_reason_details = detail.referralReasonDetails || '';
            this.errors = {};
            this.open = true;
        },

        submitForm() {
            this.loading = true;
            this.errors = {};

            const url = `/referrals/${this.referralId}`;

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
                        console.error('Edit referral failed:', err);
                        Swal.fire({
                            title: 'Error',
                            text: 'Failed to update referral. Please try again.',
                            icon: 'error',
                        });
                    }
                });
        },
    };
}
</script>