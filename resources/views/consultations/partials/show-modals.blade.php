@push('page-modals')
    @php
        $consultationPatientName = fullName($patient->last_name ?? null, $patient->first_name ?? null, $patient->middle_name ?? null, $patient->suffix ?? null);
        $consultationPatientMetaParts = [];
        if (! empty($patient->age)) {
            $consultationPatientMetaParts[] = $patient->age . ' y/o';
        }
        if (! empty($patient->sex)) {
            $consultationPatientMetaParts[] = ucfirst($patient->sex);
        }
        $consultationPatientMeta = implode(' · ', $consultationPatientMetaParts);
        $consultationVitalsSummary = $latestVitals?->summary ?? '';
    @endphp
    <div id="consultationReferralContext"
         data-patient-name="{{ e($consultationPatientName ?: '-') }}"
         data-patient-meta="{{ e($consultationPatientMeta ?: '-') }}"
         data-vitals="{{ e($consultationVitalsSummary ?: '-') }}"
         data-referral-context-url="{{ route('consultations.referral-context', $consultation->id) }}"
         class="hidden"></div>

    <x-modal name="edit-complaint-modal" title="Edit chief complaint"
             x-on:open-edit-complaint.window="open = true" x-on:close.window="open = false">
        <form action="{{ route('consultations.complaint.update', $consultation->id) }}" method="POST" class="space-y-3">
            @csrf
            @method('PUT')
            <div>
                <label for="complaint-text-edit" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Chief complaint</label>
                <textarea id="complaint-text-edit" name="complaint_text" rows="4"
                          class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                          style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);"
                          placeholder="e.g. Fever 3 days, cough">{{ $consultation->complaint_text }}</textarea>
            </div>
            <div class="flex justify-end gap-2 pt-1">
                <button type="button" @click="$dispatch('close')" class="rounded-lg border px-4 py-2 text-sm font-semibold transition hover:bg-black/[0.03]"
                        style="border-color: var(--border); color: var(--ink-muted);">Cancel</button>
                <button type="submit" class="rounded-lg px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90" style="background: var(--primary);">Save</button>
            </div>
        </form>
    </x-modal>

    <x-modal name="edit-vital-modal" title="Edit vitals"
             x-on:open-edit-vital.window="open = true; const d = $event.detail; if (d) { editVitalId = d.id; editBpSystolic = d.bp_systolic ?? ''; editBpDiastolic = d.bp_diastolic ?? ''; editTemperature = d.temperature ?? ''; editWeight = d.weight ?? ''; editHeight = d.height ?? ''; editNotes = d.notes || ''; }"
             x-on:close.window="open = false"
             x-data="{
                open: {{ session('vitals_form') === 'edit' ? 'true' : 'false' }},
                editVitalId: {{ \Illuminate\Support\Js::from(((int) old('vital_id')) ?: null) }},
                editBpSystolic: {{ \Illuminate\Support\Js::from(old('bp_systolic', '')) }},
                editBpDiastolic: {{ \Illuminate\Support\Js::from(old('bp_diastolic', '')) }},
                editTemperature: {{ \Illuminate\Support\Js::from(old('temperature', '')) }},
                editWeight: {{ \Illuminate\Support\Js::from(old('weight', '')) }},
                editHeight: {{ \Illuminate\Support\Js::from(old('height', '')) }},
                editNotes: {{ \Illuminate\Support\Js::from(old('notes', '')) }}
             }">
        <form :action="'{{ route('consultations.vitals.update', ['consultation' => $consultation->id, 'vitalId' => '__ID__']) }}'.replace('__ID__', editVitalId)" method="POST" class="space-y-3">
            @csrf
            @method('PUT')
            <input type="hidden" name="vital_id" :value="editVitalId">
            @include('consultations.partials.vitals-fields', [
                'fieldPrefix' => 'edit_',
                'required' => false,
                'values' => null,
                'alpineModels' => [
                    'bp_systolic' => 'editBpSystolic',
                    'bp_diastolic' => 'editBpDiastolic',
                    'weight' => 'editWeight',
                    'height' => 'editHeight',
                    'temperature' => 'editTemperature',
                ],
            ])
            <div>
                <label for="edit_notes" class="mb-1 block text-xs font-medium" style="color: var(--ink-muted);">Notes</label>
                <textarea id="edit_notes" name="notes" x-model="editNotes" rows="2" placeholder="Notes"
                          class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-1">
                <button type="button" @click="$dispatch('close')" class="rounded-lg border px-4 py-2 text-sm font-semibold transition hover:bg-black/[0.03]"
                        style="border-color: var(--border); color: var(--ink-muted);">Cancel</button>
                <button type="submit" class="rounded-lg px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90" style="background: var(--primary);">Update</button>
            </div>
        </form>
    </x-modal>

    <div id="diagnosisModal" x-show="$store.modals.diagnosis" x-transition.opacity.duration.200ms role="dialog" aria-modal="true" class="fixed inset-0 z-[70] flex items-center justify-center p-4" style="display: none;">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeDiagnosisModal()"></div>
        <div id="diagnosisPanel" x-show="$store.modals.diagnosis" x-data="diagnosisSearch()" @set-diagnosis-query.window="setQuery($event.detail.query)" @set-diagnosis-remarks.window="remarks = $event.detail" @preselect-diagnosis.window="preselect($event.detail.id, $event.detail.text)" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="relative bg-surface rounded-xl max-w-lg w-full max-h-[90vh] overflow-y-auto p-6 space-y-4 focus:outline-none" tabindex="-1" style="color: var(--ink);">
            <h3 id="diagnosisModalTitle" class="font-semibold text-lg">Add Diagnosis</h3>
            <div class="space-y-4">
                <div>
                    <label for="diagnosisName" class="block text-sm font-medium mb-2" style="color: var(--ink-muted);">Diagnosis Name <span class="text-[var(--danger)]" aria-hidden="true">*</span></label>
                    <div class="relative" @click.away="results = []">
                        <input type="text" id="diagnosisName" x-model="query" @input.debounce.300ms="search()" @keydown.escape="results = []" placeholder="Search ICD-10 / disease name (e.g. Dengue, Hypertension)" autocomplete="off" class="w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); --tw-ring-color: var(--primary);">
                        <input type="hidden" id="diagnosisLookupId" :value="selectedId || ''">
                        <div x-show="results.length > 0" class="absolute z-10 mt-1 max-h-60 w-full overflow-y-auto rounded-lg border bg-surface" style="display:none; border-color: var(--border);">
                            <ul>
                                <template x-for="item in results" :key="item.id">
                                    <li @click="select(item)" class="px-3 py-2 cursor-pointer text-sm border-b hover:bg-black/5" style="border-color: var(--border); color: var(--ink);" x-text="item.text"></li>
                                </template>
                            </ul>
                        </div>
                        <div x-show="query.length >= 2 && results.length === 0 && !searching" class="absolute z-10 mt-1 w-full rounded-lg border bg-surface px-3 py-2 text-sm" style="display:none; border-color: var(--border); color: var(--ink-subtle);">
                            No matches found. You can still type a custom name.
                        </div>
                    </div>
                    <p class="mt-1 text-[11px]" style="color: var(--ink-subtle);">Pick an ICD-10 entry to keep its code, or type a custom diagnosis.</p>
                </div>
                <div>
                    <label for="diagnosisRemarks" class="block text-sm font-medium mb-2" style="color: var(--ink-muted);">Remarks</label>
                    <textarea id="diagnosisRemarks" x-model="remarks" placeholder="Add remarks (optional)" rows="3" class="w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); --tw-ring-color: var(--primary); resize: vertical;"></textarea>
                </div>
                <div x-show="dxPreview" class="rounded-xl border px-3 py-2 text-sm" style="display:none; border-color: var(--primary); background: var(--teal-soft); color: var(--ink);">
                    <span class="font-semibold uppercase tracking-wide text-[11px]" style="color: var(--primary);">Dx preview</span>
                    <p class="mt-0.5 font-medium" x-text="dxPreview"></p>
                </div>
            </div>
            <div class="flex gap-2 justify-end">
                <button type="button" class="px-4 py-2 rounded-lg text-sm font-medium transition" style="background: var(--border); color: var(--ink);" onclick="closeDiagnosisModal()">Cancel</button>
                <button type="button" id="diagnosisSubmitBtn" class="px-4 py-2 rounded-lg text-white text-sm font-medium transition" style="background: var(--primary);" onclick="addDiagnosis()">Add</button>
            </div>
        </div>
    </div>

    <div id="prescriptionModal" x-show="$store.modals.prescription" x-transition.opacity.duration.200ms role="dialog" aria-modal="true" class="fixed inset-0 z-[70] flex items-center justify-center p-4" style="display: none;">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closePrescriptionModal()"></div>
        <div id="prescriptionPanel" x-show="$store.modals.prescription" x-data="medicineSearch()" @set-medicine-query.window="setQuery($event.detail.query)" @preselect-medicine.window="preselect($event.detail.id, $event.detail.text)" @fill-prescription.window="fill($event.detail)" @reset-medicine.window="resetForm()" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="relative bg-surface rounded-xl max-w-lg w-full max-h-[90vh] overflow-y-auto p-6 space-y-4 focus:outline-none" tabindex="-1" style="color: var(--ink);">
            <div>
                <h3 id="prescriptionModalTitle" class="font-semibold text-lg">Add Prescription</h3>
                <p class="mt-1 text-[11px]" style="color: var(--ink-subtle);"><span class="text-[var(--danger)]" aria-hidden="true">*</span> Required. Medicine, dosage, route, frequency, duration and quantity must be completed before saving.</p>
            </div>
            <div class="space-y-4">
                <div>
                    <label for="medicineName" class="block text-sm font-medium mb-2" style="color: var(--ink-muted);">Medicine Name <span class="text-[var(--danger)]" aria-hidden="true">*</span></label>
                    <div class="relative" @click.away="results = []">
                        <input type="text" id="medicineName" x-model="query" @input.debounce.300ms="search()" @keydown.escape="results = []" placeholder="Search medicine (e.g. Paracetamol, Amoxicillin)" autocomplete="off" class="w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); --tw-ring-color: var(--primary);">
                        <input type="hidden" id="medicineLookupId" :value="selectedId || ''">
                        <div x-show="results.length > 0" class="absolute inset-x-0 top-full z-10 mt-1 max-h-56 overflow-y-auto rounded-lg border bg-surface shadow-sm" style="display:none; border-color: var(--border);">
                            <ul>
                                <template x-for="item in results" :key="item.id">
                                    <li @click="select(item)" class="px-3 py-2 text-sm cursor-pointer border-b last:border-b-0 hover:bg-black/5" style="border-color: var(--border); color: var(--ink);">
                                        <span x-text="item.text"></span>
                                        <span x-show="item.form" class="ml-1 rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide" style="background: var(--teal-soft); color: var(--primary);" x-text="item.form"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                        <div x-show="query.length >= 2 && results.length === 0 && !searching" class="absolute inset-x-0 top-full z-10 mt-1 rounded-lg border bg-surface px-3 py-2 text-sm" style="display:none; border-color: var(--border); color: var(--ink-subtle);">
                            No matches found. You can still type a custom name.
                        </div>
                    </div>
                    <div x-show="selectedText" class="mt-2 flex items-center justify-between gap-3 rounded-lg border px-3 py-2 text-sm" style="display:none; border-color: var(--primary); background: var(--teal-soft); color: var(--primary);">
                        <span class="font-medium truncate">Directory: <span x-text="selectedText"></span></span>
                        <button type="button" @click="clearSelection()" class="shrink-0 text-xs font-semibold hover:underline">Clear</button>
                    </div>
                    <p class="mt-1 text-[11px]" style="color: var(--ink-subtle);">Pick from the medicine directory to keep it linked, or type a custom name.</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="dosage" class="block text-sm font-medium mb-2" style="color: var(--ink-muted);">Dosage <span class="text-[var(--danger)]" aria-hidden="true">*</span></label>
                        <input type="text" id="dosage" x-model="dosage" placeholder="e.g. 500mg" class="w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); --tw-ring-color: var(--primary);">
                    </div>
                    <div>
                        <label for="route" class="block text-sm font-medium mb-2" style="color: var(--ink-muted);">Route <span class="text-[var(--danger)]" aria-hidden="true">*</span></label>
                        <select id="route" x-model="route" class="w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); --tw-ring-color: var(--primary);">
                            <option value="PO">PO (by mouth)</option>
                            <option value="IV">IV (intravenous)</option>
                            <option value="IM">IM (intramuscular)</option>
                            <option value="SC">SC (subcutaneous)</option>
                            <option value="Topical">Topical</option>
                            <option value="Inhalation">Inhalation</option>
                            <option value="Ophthalmic">Ophthalmic (eye)</option>
                            <option value="Otic">Otic (ear)</option>
                            <option value="Rectal">Rectal</option>
                            <option value="Vaginal">Vaginal</option>
                            <option value="Sublingual">Sublingual</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="frequency" class="block text-sm font-medium mb-2" style="color: var(--ink-muted);">Frequency <span class="text-[var(--danger)]" aria-hidden="true">*</span></label>
                        <input type="text" id="frequency" x-model="frequency" placeholder="e.g. TID" class="w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); --tw-ring-color: var(--primary);">
                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                            <template x-for="f in frequencyOptions" :key="f">
                                <button type="button" @click="frequency = f" class="px-2 py-0.5 rounded-full text-[11px] transition hover:opacity-80" :class="frequency === f ? 'bg-[var(--primary)] text-white' : 'bg-teal-soft text-[var(--primary)]'" x-text="f"></button>
                            </template>
                        </div>
                    </div>
                    <div>
                        <label for="duration" class="block text-sm font-medium mb-2" style="color: var(--ink-muted);">Duration <span class="text-[var(--danger)]" aria-hidden="true">*</span></label>
                        <input type="text" id="duration" x-model="duration" placeholder="e.g. 7 days" class="w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); --tw-ring-color: var(--primary);">
                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                            <template x-for="d in durationOptions" :key="d">
                                <button type="button" @click="duration = d" class="px-2 py-0.5 rounded-full text-[11px] transition hover:opacity-80" :class="duration === d ? 'bg-[var(--primary)] text-white' : 'bg-teal-soft text-[var(--primary)]'" x-text="d"></button>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="quantity" class="block text-sm font-medium mb-2" style="color: var(--ink-muted);">Quantity <span class="text-[var(--danger)]" aria-hidden="true">*</span></label>
                        <div class="flex items-center gap-2">
                            <input type="number" id="quantity" x-model="quantity" placeholder="e.g. 21" min="1" class="w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); --tw-ring-color: var(--primary);">
                            <button type="button" x-show="suggestedQuantity" @click="fillQuantity()" class="shrink-0 px-2.5 py-2 rounded-lg text-xs font-semibold border transition hover:opacity-80" style="display:none; border-color: var(--primary); color: var(--primary); background: var(--teal-soft);">
                                Suggested: <span x-text="suggestedQuantity"></span>
                            </button>
                        </div>
                        <p class="mt-1 text-[11px]" style="color: var(--ink-subtle);">Computed from dose, frequency and duration.</p>
                    </div>
                    <div>
                        <label for="instructions" class="block text-sm font-medium mb-2" style="color: var(--ink-muted);">Instructions</label>
                        <input type="text" id="instructions" x-model="instructions" placeholder="e.g. After meals" class="w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); --tw-ring-color: var(--primary);">
                    </div>
                </div>

                <div x-show="rxPreview" class="rounded-xl border px-3 py-2 text-sm" style="display:none; border-color: var(--primary); background: var(--teal-soft); color: var(--ink);">
                    <span class="font-semibold uppercase tracking-wide text-[11px]" style="color: var(--primary);">Rx preview</span>
                    <p class="mt-0.5 font-medium" x-text="rxPreview"></p>
                </div>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
                <button type="button" class="px-4 py-2 rounded-lg text-sm font-medium transition" style="background: var(--border); color: var(--ink);" onclick="closePrescriptionModal()">Cancel</button>
                <button type="button" id="prescriptionSubmitBtn" class="px-4 py-2 rounded-lg text-white text-sm font-medium transition" style="background: var(--primary);" onclick="addPrescription()">Add</button>
            </div>
        </div>
    </div>

    <div id="outwardReferralShowModal" x-show="$store.modals.outward" x-transition.opacity.duration.200ms role="dialog" aria-modal="true" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeConsultationOutwardReferralWizard()"></div>
        <div id="outwardReferralShowPanel" x-show="$store.modals.outward" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="relative w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-2xl shadow-2xl bg-surface focus:outline-none" tabindex="-1">
            <div class="flex items-center justify-between gap-3 border-b px-5 py-4" style="border-color: var(--border);">
                <div>
                    <h2 class="font-display text-lg font-semibold" style="color: var(--ink);">Outward Referral</h2>
                    <p class="text-xs" style="color: var(--ink-muted);">Refer patient to a higher-level facility</p>
                </div>
                <button type="button" onclick="closeConsultationOutwardReferralWizard()" class="shrink-0 rounded-full p-2 transition hover:bg-black/5" style="color: var(--ink-muted);" aria-label="Close referral wizard">
                    <i class="fa-solid fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <div class="p-5">
                @php
                    $destinationFacilities = [
                        'Tagoloan Rural Health Unit (RHU)',
                        'Tagoloan District Hospital',
                        'Provincial Hospital',
                        'Tagoloan Polymedic Clinic',
                        'Northern Mindanao Medical Center (NMMC)',
                        'Cagayan de Oro Medical Center',
                        'Other facility (specify in notes)',
                    ];
                    $referralReasonOptions = [
                        'specialized_evaluation' => 'Need for specialized medical evaluation / physician',
                        'lack_diagnostics' => 'Lack of diagnostic equipment / laboratory tests',
                        'lack_medicines' => 'Lack of available medicines / vaccines',
                        'emergency_trauma' => 'Emergency / trauma stabilization required',
                    ];
                @endphp
                @include('referrals.partials.outreferrals', [
                    'destinationFacilities' => $destinationFacilities,
                    'referralReasonOptions' => $referralReasonOptions,
                    'patient' => $patient,
                ])
            </div>
        </div>
    </div>
@endpush

@push('scripts')
<script>
const CONSULTATION_ID = {{ $consultation->id }};
const CSRF_TOKEN = document.querySelector('input[name="_token"]')?.value || '{{ csrf_token() }}';
let editMode = { diagnosis: false, prescription: false };
let editId = { diagnosis: null, prescription: null };

function isSafeFetchHandledError(err) {
    const message = (err && err.message) || '';
    return message === 'Session expired'
        || message === 'Forbidden'
        || message === 'Not found'
        || message.indexOf('Server error') === 0;
}

function diagnosisSearch() {
    return {
        query: '',
        results: [],
        selectedId: null,
        selectedText: '',
        searching: false,
        remarks: '',
        async search() {
            if (this.query !== this.selectedText) {
                this.selectedId = null;
                this.selectedText = '';
            }
            if (this.query.length < 2) {
                this.results = [];
                return;
            }
            this.searching = true;
            try {
                const response = await safeFetch('{{ route('search.diagnoses') }}?query=' + encodeURIComponent(this.query));
                this.results = response.ok ? await response.json() : [];
            } catch (error) {
                this.results = [];
            } finally {
                this.searching = false;
            }
        },
        select(item) {
            this.query = item.text;
            this.selectedId = item.id;
            this.selectedText = item.text;
            this.results = [];
        },
        setQuery(term) {
            this.query = term;
            this.selectedId = null;
            this.selectedText = '';
            this.results = [];
        },
        preselect(id, text) {
            this.query = text || '';
            this.selectedId = id || null;
            this.selectedText = text || '';
            this.results = [];
        },
        get dxPreview() {
            const name = this.query.trim();
            if (!name) return '';
            let line = name;
            if (this.remarks.trim()) line += ' \u2014 ' + this.remarks.trim();
            return line;
        },
    };
}

const FREQUENCY_OPTIONS = ['OD', 'BID', 'TID', 'QID', 'EOD'];
const DURATION_OPTIONS = ['3 days', '5 days', '7 days', '10 days', '14 days'];

function medicineSearch() {
    return {
        query: '',
        results: [],
        selectedId: null,
        selectedText: '',
        selectedForm: '',
        searching: false,
        dosage: '',
        route: 'PO',
        frequency: '',
        duration: '',
        quantity: '',
        instructions: '',
        frequencyOptions: FREQUENCY_OPTIONS,
        durationOptions: DURATION_OPTIONS,
        get completeSig() {
            return Boolean(this.dosage && this.route && this.frequency && this.duration && this.quantity && Number(this.quantity) >= 1);
        },
        get rxPreview() {
            const med = this.selectedText || this.query.trim();
            if (!med || !this.completeSig) return '';
            const parts = [this.dosage, this.route, this.frequency, this.duration ? 'x ' + this.duration : ''].filter(Boolean);
            let line = med + ' \u2014 ' + parts.join(' ');
            if (this.instructions) line += '. ' + this.instructions;
            line += ' (Qty ' + this.quantity + ')';
            return line;
        },
        get suggestedQuantity() {
            const units = this.parseDosageUnits();
            const times = this.parseFrequencyTimes();
            const days = this.parseDurationDays();
            if (!units || !times || !days) return null;
            return units * times * days;
        },
        async search() {
            if (this.query !== this.selectedText) {
                this.selectedId = null;
                this.selectedText = '';
                this.selectedForm = '';
            }
            if (this.query.length < 2) {
                this.results = [];
                return;
            }
            this.searching = true;
            try {
                const response = await safeFetch('{{ route('search.medicines') }}?query=' + encodeURIComponent(this.query));
                this.results = response.ok ? await response.json() : [];
            } catch (error) {
                this.results = [];
            } finally {
                this.searching = false;
            }
        },
        select(item) {
            this.query = item.text;
            this.selectedId = item.id;
            this.selectedText = item.text;
            this.selectedForm = item.form || '';
            this.results = [];
        },
        clearSelection() {
            this.selectedId = null;
            this.selectedText = '';
            this.selectedForm = '';
            this.query = '';
            this.results = [];
        },
        setQuery(term) {
            this.query = term;
            this.selectedId = null;
            this.selectedText = '';
            this.selectedForm = '';
            this.results = [];
        },
        preselect(id, text) {
            this.query = text || '';
            this.selectedId = id || null;
            this.selectedText = text || '';
            this.selectedForm = '';
            this.results = [];
        },
        fill(detail) {
            this.preselect(detail.medicine_id, detail.medicine_name);
            this.dosage = detail.dosage || '';
            this.route = detail.route || 'PO';
            this.frequency = detail.frequency || '';
            this.duration = detail.duration || '';
            this.quantity = detail.quantity === null || detail.quantity === undefined ? '' : String(detail.quantity);
            this.instructions = detail.instructions || '';
        },
        resetForm() {
            this.preselect(null, '');
            this.dosage = '';
            this.route = 'PO';
            this.frequency = '';
            this.duration = '';
            this.quantity = '';
            this.instructions = '';
        },
        fillQuantity() {
            if (this.suggestedQuantity) this.quantity = String(this.suggestedQuantity);
        },
        parseDosageUnits() {
            const match = this.dosage.trim().match(/^(\d+(?:\.\d+)?)\s*(tab|tabs|tablet|tablets|cap|caps|capsule|capsules|mL|ml|sachet|puff|drop|drops|amp|vial)/i);
            return match ? parseFloat(match[1]) : null;
        },
        parseFrequencyTimes() {
            const f = this.frequency.toLowerCase();
            if (f.includes('eod') || f.includes('every other')) return null;
            if (f.includes('every 4')) return 6;
            if (f.includes('every 6')) return 4;
            if (f.includes('every 8')) return 3;
            if (f.includes('qid') || f.includes('4x') || f.includes('4 times')) return 4;
            if (f.includes('tid') || f.includes('3x') || f.includes('3 times')) return 3;
            if (f.includes('bid') || f.includes('2x') || f.includes('2 times')) return 2;
            if (f.includes('od') || f.includes('once daily') || f.includes('daily') || f.includes('bedtime') || f.includes('nocte')) return 1;
            return null;
        },
        parseDurationDays() {
            const match = this.duration.trim().match(/^(\d+)\s*(day|days)/i);
            if (match) return parseInt(match[1], 10);
            if (/^1\s*month/i.test(this.duration)) return 30;
            if (/^2\s*month/i.test(this.duration)) return 60;
            return null;
        },
    };
}

function openDiagnosisModal() {
    editMode.diagnosis = false;
    editId.diagnosis = null;
    document.getElementById('diagnosisModalTitle').textContent = 'Add Diagnosis';
    document.getElementById('diagnosisSubmitBtn').textContent = 'Add';
    window.dispatchEvent(new CustomEvent('set-diagnosis-query', { detail: { query: '' } }));
    window.dispatchEvent(new CustomEvent('set-diagnosis-remarks', { detail: '' }));
    showModal('diagnosisModal', 'diagnosisPanel');
}

function closeDiagnosisModal() {
    hideModal('diagnosisModal', 'diagnosisPanel');
    window.dispatchEvent(new CustomEvent('set-diagnosis-query', { detail: { query: '' } }));
    window.dispatchEvent(new CustomEvent('set-diagnosis-remarks', { detail: '' }));
    editMode.diagnosis = false;
    editId.diagnosis = null;
}

function openPrescriptionModal() {
    editMode.prescription = false;
    editId.prescription = null;
    document.getElementById('prescriptionModalTitle').textContent = 'Add Prescription';
    document.getElementById('prescriptionSubmitBtn').textContent = 'Add';
    window.dispatchEvent(new CustomEvent('reset-medicine'));
    showModal('prescriptionModal', 'prescriptionPanel');
}

function closePrescriptionModal() {
    hideModal('prescriptionModal', 'prescriptionPanel');
    window.dispatchEvent(new CustomEvent('reset-medicine'));
    editMode.prescription = false;
    editId.prescription = null;
}

function addDiagnosis() {
    const name = document.getElementById('diagnosisName').value.trim();
    const remarks = document.getElementById('diagnosisRemarks').value.trim();

    if (!name) {
        Swal.fire({ title: 'Missing diagnosis', text: 'Please enter a diagnosis name.', icon: 'warning', confirmButtonColor: 'var(--primary)' });
        return;
    }

    const lookupId = document.getElementById('diagnosisLookupId').value;
    const payload = {
        diagnosis_name: name,
        diagnosis_id: lookupId === '' ? null : parseInt(lookupId, 10),
        remarks,
    };
    const url = editMode.diagnosis && editId.diagnosis
        ? `/consultations/${CONSULTATION_ID}/diagnoses/${editId.diagnosis}`
        : `/consultations/${CONSULTATION_ID}/edit-diagnosis`;
    const method = editMode.diagnosis ? 'PUT' : 'POST';

    safeFetch(url, {
        method,
        headers: {
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify(payload),
    })
        .then((response) => {
            if (!response.ok) return response.json().then((err) => { throw err; });
            return response.json();
        })
        .then(() => {
            closeDiagnosisModal();
            location.reload();
        })
        .catch((err) => {
            if (isSafeFetchHandledError(err)) return;
            const msg = err.message || (err.errors ? Object.values(err.errors).flat().join(', ') : 'Failed to save diagnosis.');
            Swal.fire({ title: 'Error', text: msg, icon: 'error', confirmButtonColor: 'var(--primary)' });
        });
}

function addPrescription() {
    const medicineName = document.getElementById('medicineName').value.trim();
    const dosage = document.getElementById('dosage').value.trim();
    const route = document.getElementById('route').value;
    const frequency = document.getElementById('frequency').value.trim();
    const duration = document.getElementById('duration').value.trim();
    const quantity = document.getElementById('quantity').value.trim();
    const instructions = document.getElementById('instructions').value.trim();

    const missing = [];
    if (!medicineName) missing.push('medicine name');
    if (!dosage) missing.push('dosage');
    if (!route) missing.push('route');
    if (!frequency) missing.push('frequency');
    if (!duration) missing.push('duration');
    if (!quantity || parseInt(quantity, 10) < 1) missing.push('quantity');

    if (missing.length) {
        Swal.fire({
            title: 'Incomplete prescription',
            text: 'Please complete: ' + missing.join(', ') + '.',
            icon: 'warning',
            confirmButtonColor: 'var(--primary)',
        });
        return;
    }

    const lookupId = document.getElementById('medicineLookupId').value;
    const payload = {
        medicine_name: medicineName,
        medicine_id: lookupId === '' ? null : parseInt(lookupId, 10),
        dosage,
        route,
        frequency,
        duration,
        quantity: parseInt(quantity, 10),
        instructions,
    };
    const url = editMode.prescription && editId.prescription
        ? `/consultations/${CONSULTATION_ID}/prescriptions/${editId.prescription}`
        : `/consultations/${CONSULTATION_ID}/edit-prescription`;
    const method = editMode.prescription ? 'PUT' : 'POST';

    safeFetch(url, {
        method,
        headers: {
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify(payload),
    })
        .then((response) => {
            if (!response.ok) return response.json().then((err) => { throw err; });
            return response.json();
        })
        .then(() => {
            closePrescriptionModal();
            location.reload();
        })
        .catch((err) => {
            if (isSafeFetchHandledError(err)) return;
            const msg = err.message || (err.errors ? Object.values(err.errors).flat().join(', ') : 'Failed to save prescription.');
            Swal.fire({ title: 'Error', text: msg, icon: 'error', confirmButtonColor: 'var(--primary)' });
        });
}

window.addEventListener('open-diagnosis-modal', () => openDiagnosisModal());
window.addEventListener('open-prescription-modal', () => openPrescriptionModal());

window.addEventListener('edit-diagnosis', (e) => {
    const d = e.detail;
    editMode.diagnosis = true;
    editId.diagnosis = d.id;
    document.getElementById('diagnosisModalTitle').textContent = 'Edit Diagnosis';
    document.getElementById('diagnosisSubmitBtn').textContent = 'Update';
    window.dispatchEvent(new CustomEvent('preselect-diagnosis', { detail: { id: d.lookupId, text: d.name || '' } }));
    window.dispatchEvent(new CustomEvent('set-diagnosis-remarks', { detail: d.remarks || '' }));
    showModal('diagnosisModal', 'diagnosisPanel');
});

window.addEventListener('delete-diagnosis', (e) => {
    Swal.fire({
        title: 'Delete diagnosis?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: 'var(--danger)',
        cancelButtonColor: 'var(--ink-muted)',
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (!result.isConfirmed) return;
        safeFetch(`/consultations/${CONSULTATION_ID}/diagnoses/${e.detail.id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
        })
            .then((response) => {
                if (response.ok) {
                    location.reload();
                } else {
                    Swal.fire({ title: 'Error', text: 'Failed to delete diagnosis.', icon: 'error', confirmButtonColor: 'var(--primary)' });
                }
            })
            .catch((err) => {
                if (isSafeFetchHandledError(err)) return;
                Swal.fire({ title: 'Error', text: 'Error deleting diagnosis.', icon: 'error', confirmButtonColor: 'var(--primary)' });
            });
    });
});

window.addEventListener('edit-prescription', (e) => {
    const d = e.detail;
    editMode.prescription = true;
    editId.prescription = d.id;
    document.getElementById('prescriptionModalTitle').textContent = 'Edit Prescription';
    document.getElementById('prescriptionSubmitBtn').textContent = 'Update';
    window.dispatchEvent(new CustomEvent('fill-prescription', {
        detail: {
            medicine_id: d.medicineId,
            medicine_name: d.name,
            dosage: d.dosage,
            route: d.route,
            frequency: d.frequency,
            duration: d.duration,
            quantity: d.quantity,
            instructions: d.instructions,
        },
    }));
    showModal('prescriptionModal', 'prescriptionPanel');
});

window.addEventListener('delete-prescription', (e) => {
    Swal.fire({
        title: 'Delete prescription?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: 'var(--danger)',
        cancelButtonColor: 'var(--ink-muted)',
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (!result.isConfirmed) return;
        safeFetch(`/consultations/${CONSULTATION_ID}/prescriptions/${e.detail.id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
        })
            .then((response) => {
                if (response.ok) {
                    location.reload();
                } else {
                    Swal.fire({ title: 'Error', text: 'Failed to delete prescription.', icon: 'error', confirmButtonColor: 'var(--primary)' });
                }
            })
            .catch((err) => {
                if (isSafeFetchHandledError(err)) return;
                Swal.fire({ title: 'Error', text: 'Error deleting prescription.', icon: 'error', confirmButtonColor: 'var(--primary)' });
            });
    });
});

document.getElementById('diagnosisModal')?.addEventListener('click', function (e) {
    if (e.target === this) closeDiagnosisModal();
});

document.getElementById('prescriptionModal')?.addEventListener('click', function (e) {
    if (e.target === this) closePrescriptionModal();
});

function confirmVitalsDelete(form) {
    Swal.fire({
        title: 'Delete this vitals version?',
        text: 'This cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: 'var(--danger)',
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
    }).then(function (result) {
        if (result.isConfirmed) form.submit();
    });
    return false;
}
</script>
@endpush
