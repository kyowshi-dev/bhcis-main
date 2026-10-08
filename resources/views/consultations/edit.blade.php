@extends('layouts.app')

@section('title', 'Edit Consultation')

@php
    $age = \Carbon\Carbon::parse($patient->date_of_birth)->age;
@endphp

@section('content')
<form action="{{ route('consultations.update', $consultation->id) }}" method="POST" id="consultationForm" class="space-y-5 lg:space-y-6">
    @csrf
    @method('PUT')
    
    <div class="space-y-4">
        <!-- Header Section -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Edit Consultation</h1>
                <p class="text-sm mt-1" style="color: var(--ink-muted);">{{ \App\Helpers\PatientCode::format((int) $consultation->patient_id) }} - {{ fullName($patient->last_name, $patient->first_name, $patient->middle_name, $patient->suffix) }}</p>
            </div>
        </div>

        <!-- Slim Horizontal Info Bar -->
        <div class="rounded-lg border px-4 py-3 flex flex-wrap gap-4 lg:gap-6 text-sm" style="background: var(--bg-surface); border-color: var(--border);">
            <div>
                <p style="color: var(--ink-muted);" class="text-xs font-medium">DATE</p>
                <p style="color: var(--ink);" class="font-medium">{{ \Carbon\Carbon::parse($consultation->created_at)->format(\App\Helpers\DateFormat::DATE_SQL.' H:i A') }}</p>
            </div>
            <div>
                <p style="color: var(--ink-muted);" class="text-xs font-medium">STATUS</p>
                <p style="color: var(--ink);" class="font-medium">{{ $consultation->status_label }}</p>
            </div>
            <div>
                <p style="color: var(--ink-muted);" class="text-xs font-medium">HEALTH WORKER</p>
                <p class="font-medium" style="color: var(--ink);">{{ $consultation->worker_name ?: 'Not assigned' }}</p>
            </div>
            <div>
                <p style="color: var(--ink-muted);" class="text-xs font-medium">NATURE OF VISIT</p>
                <p style="color: var(--ink);" class="font-medium">{{ $consultation->nature_of_visit }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-5 lg:space-y-6">

            <!-- Diagnoses Section -->
            <div class="rounded-xl border p-5 lg:p-6 space-y-4" style="background: var(--bg-surface); border-color: var(--border);">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold text-lg" style="color: var(--ink);">Diagnoses</h2>
                    <button type="button" class="px-3 py-1 rounded-lg text-xs font-medium transition" style="background: var(--teal-soft); color: var(--primary);" onclick="openDiagnosisModal()">+ Add Diagnosis</button>
                </div>
                
                <div id="diagnosesList" class="space-y-3">
                    @if ($diagnoses->count() > 0)
                        @foreach ($diagnoses as $diagnosis)
                            @php
                                $diagName = $diagnosis->display_name ?? 'Unknown';
                            @endphp
                            <div class="p-3 rounded-lg border flex items-start justify-between group" style="background: var(--border); border-color: var(--border);">
                                <div class="flex-1">
                                    <p class="text-sm font-medium" style="color: var(--ink);">{{ $diagName }}</p>
                                    @if ($diagnosis->remarks)
                                        <p class="text-xs mt-1" style="color: var(--ink-muted);">{{ $diagnosis->remarks }}</p>
                                    @endif
                                </div>
                            <div class="flex items-center gap-2">
                                <button type="button" class="p-1 rounded text-[var(--ink-subtle)] hover:text-[var(--accent-blue)] transition focus-visible:opacity-100" title="Edit diagnosis" onclick="editDiagnosis({{ $diagnosis->id }}, {{ Js::from($diagName) }}, {{ Js::from($diagnosis->remarks) }}, {{ Js::from($diagnosis->diagnosis_id) }})">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                <button type="button" class="p-1 rounded text-[var(--ink-subtle)] hover:text-[var(--danger)] transition focus-visible:opacity-100" title="Delete diagnosis" aria-label="Delete diagnosis" onclick="deleteDiagnosis({{ $diagnosis->id }})">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-sm italic py-4" style="color: var(--ink-muted);">No diagnoses recorded. Click "Add Diagnosis" to get started.</p>
                    @endif
                </div>
            </div>

            <!-- Prescriptions Section -->
            <div class="rounded-xl border p-5 lg:p-6 space-y-4" style="background: var(--bg-surface); border-color: var(--border);">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold text-lg" style="color: var(--ink);">Prescriptions</h2>
                    @if ($canModifyPrescriptions ?? false)
                        <button type="button" class="px-3 py-1 rounded-lg text-xs font-medium transition" style="background: var(--teal-soft); color: var(--primary);" onclick="openPrescriptionModal()">+ Add Prescription</button>
                    @endif
                </div>
                
                <div id="prescriptionsList" class="space-y-3">
                    @if ($prescriptions->count() > 0)
                        @foreach ($prescriptions as $prescription)
                            @php
                                $medName = $prescription->display_name ?? 'Unknown';
                            @endphp
                            <div class="p-3 rounded-lg border group" style="background: var(--border); border-color: var(--border);">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium" style="color: var(--ink);">{{ $medName }}</p>
                                        <div class="grid grid-cols-2 gap-2 mt-2 text-xs" style="color: var(--ink-muted);">
                                            @if ($prescription->dosage)
                                                <p><span class="font-medium">Dosage:</span> {{ $prescription->dosage }}</p>
                                            @endif
                                            @if ($prescription->route)
                                                <p><span class="font-medium">Route:</span> {{ $prescription->route }}</p>
                                            @endif
                                            @if ($prescription->frequency)
                                                <p><span class="font-medium">Frequency:</span> {{ $prescription->frequency }}</p>
                                            @endif
                                            @if ($prescription->duration)
                                                <p><span class="font-medium">Duration:</span> {{ $prescription->duration }}</p>
                                            @endif
                                            @if ($prescription->quantity)
                                                <p><span class="font-medium">Quantity:</span> {{ $prescription->quantity }}</p>
                                            @endif
                                            @if ($prescription->instructions)
                                                <p class="col-span-2"><span class="font-medium">Instructions:</span> {{ $prescription->instructions }}</p>
                                            @endif
                                        </div>
                                    </div>
                            @if ($canModifyPrescriptions ?? false)
                            <div class="flex items-center gap-2">
                                <button type="button" class="p-1 rounded text-[var(--ink-subtle)] hover:text-[var(--accent-blue)] transition focus-visible:opacity-100" title="Edit prescription" onclick="editPrescription({{ $prescription->id }}, {{ Js::from($medName) }}, {{ Js::from($prescription->dosage) }}, {{ Js::from($prescription->route) }}, {{ Js::from($prescription->frequency) }}, {{ Js::from($prescription->duration) }}, {{ Js::from($prescription->quantity) }}, {{ Js::from($prescription->instructions) }}, {{ Js::from($prescription->medicine_id) }})">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </button>
                                <button type="button" class="p-1 rounded text-[var(--ink-subtle)] hover:text-[var(--danger)] transition focus-visible:opacity-100" title="Delete prescription" aria-label="Delete prescription" onclick="deletePrescription({{ $prescription->id }})">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                            @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-sm italic py-4" style="color: var(--ink-muted);">{{ ($canModifyPrescriptions ?? false) ? 'No prescriptions recorded. Click "Add Prescription" to get started.' : 'No prescriptions recorded.' }}</p>
                    @endif
                    @unless ($canModifyPrescriptions ?? false)
                        <p class="text-xs" style="color: var(--ink-muted);">Only doctors and administrators can modify prescriptions.</p>
                    @endunless
                </div>
            </div>

            <!-- Quick Notes - Primary Focal Point -->
            <div class="rounded-xl border p-5 lg:p-6 space-y-4" style="background: var(--bg-surface); border-color: var(--border);">
                <h2 class="font-semibold text-lg" style="color: var(--ink);">Quick Notes</h2>
                <textarea name="notes" placeholder="Add detailed notes about this consultation..." rows="6" class="w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary); resize: vertical;">{{ $consultation->notes }}</textarea>
                <div class="flex gap-3">
                    <button type="submit" class="px-6 py-2 rounded-xl text-white text-sm font-semibold transition" style="background: var(--primary);">Update Consultation</button>
                    <a href="{{ route('consultations.show', $consultation->id) }}" class="px-4 py-2 rounded-xl text-sm font-semibold transition" style="background: var(--border); color: var(--ink);">View Full Record</a>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-5 lg:space-y-6">
            <!-- Patient Info Card -->
            <div class="rounded-xl border p-5 lg:p-6 space-y-4" style="background: var(--bg-surface); border-color: var(--border);">
                <h3 class="font-semibold text-base" style="color: var(--ink);">Patient Information</h3>
                
                <div class="space-y-3 text-sm">
                    <div>
                        <p style="color: var(--ink-muted);">Name</p>
                        <p class="font-medium" style="color: var(--ink);">{{ fullName($patient->last_name, $patient->first_name, $patient->middle_name, $patient->suffix) }}</p>
                    </div>
                    <div>
                        <p style="color: var(--ink-muted);">Patient ID</p>
                        <p class="font-medium" style="color: var(--ink);">PT{{ str_pad($patient->id, 3, '0', STR_PAD_LEFT) }}</p>
                    </div>
                    <div>
                        <p style="color: var(--ink-muted);">Date of Birth</p>
                        <p class="font-medium" style="color: var(--ink);">{{ \Carbon\Carbon::parse($patient->date_of_birth)->format('Y-m-d') }} <span style="color: var(--ink-muted);">({{ $age }}y)</span></p>
                    </div>
                    <div>
                        <p style="color: var(--ink-muted);">Sex</p>
                        <p class="font-medium" style="color: var(--ink);">{{ $patient->sex }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@push('page-modals')
<!-- Diagnosis Modal -->
<div id="diagnosisModal" x-show="$store.modals.diagnosis" x-transition.opacity.duration.200ms role="dialog" aria-modal="true" class="fixed inset-0 z-[70] flex items-center justify-center p-4" style="display: none;">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeDiagnosisModal()"></div>
    <div id="diagnosisPanel" x-show="$store.modals.diagnosis" x-data="editDiagnosisSearch()" @set-diagnosis-query.window="setQuery($event.detail.query)" @preselect-diagnosis.window="preselect($event.detail.id, $event.detail.text)" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="relative bg-surface rounded-xl max-w-lg w-full max-h-[90vh] overflow-y-auto p-6 space-y-4 focus:outline-none" tabindex="-1" style="color: var(--ink);">
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
                <label class="block text-sm font-medium mb-2" style="color: var(--ink-muted);">Remarks</label>
                <textarea id="diagnosisRemarks" placeholder="Add remarks (optional)" rows="3" class="w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); --tw-ring-color: var(--primary); resize: vertical;"></textarea>
            </div>
        </div>
        <div class="flex gap-2 justify-end">
            <button type="button" class="px-4 py-2 rounded-lg text-sm font-medium transition" style="background: var(--border); color: var(--ink);" onclick="closeDiagnosisModal()">Cancel</button>
            <button type="button" id="diagnosisSubmitBtn" class="px-4 py-2 rounded-lg text-white text-sm font-medium transition" style="background: var(--primary);" onclick="addDiagnosis()">Add</button>
        </div>
    </div>
</div>

<!-- Prescription Modal -->
<div id="prescriptionModal" x-show="$store.modals.prescription" x-transition.opacity.duration.200ms role="dialog" aria-modal="true" class="fixed inset-0 z-[70] flex items-center justify-center p-4" style="display: none;">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closePrescriptionModal()"></div>
    <div id="prescriptionPanel" x-show="$store.modals.prescription" x-data="editMedicineSearch()" @set-medicine-query.window="setQuery($event.detail.query)" @preselect-medicine.window="preselect($event.detail.id, $event.detail.text)" @fill-prescription.window="fill($event.detail)" @reset-medicine.window="resetForm()" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="relative bg-surface rounded-xl max-w-lg w-full max-h-[90vh] overflow-y-auto p-6 space-y-4 focus:outline-none" tabindex="-1" style="color: var(--ink);">
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
                    <input type="text" id="dosage" x-model="dosage" placeholder="e.g., 500mg" class="w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); --tw-ring-color: var(--primary);">
                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                        <template x-for="unit in dosageUnits" :key="unit">
                            <button type="button" @click="dosage = unit" class="px-2 py-0.5 rounded-full text-[11px] transition hover:opacity-80" :class="dosage === unit ? 'bg-[var(--primary)] text-white' : 'bg-teal-soft text-[var(--primary)]'" x-text="unit"></button>
                        </template>
                    </div>
                </div>
                <div>
                    <label for="route" class="block text-sm font-medium mb-2" style="color: var(--ink-muted);">Route <span class="text-[var(--danger)]" aria-hidden="true">*</span></label>
                    <select id="route" x-model="route" class="w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); --tw-ring-color: var(--primary);">
                        <option value="">Select route</option>
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
                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                        <template x-for="r in quickRoutes" :key="r">
                            <button type="button" @click="route = r" class="px-2 py-0.5 rounded-full text-[11px] transition hover:opacity-80" :class="route === r ? 'bg-[var(--primary)] text-white' : 'bg-teal-soft text-[var(--primary)]'" x-text="r"></button>
                        </template>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="frequency" class="block text-sm font-medium mb-2" style="color: var(--ink-muted);">Frequency <span class="text-[var(--danger)]" aria-hidden="true">*</span></label>
                    <input type="text" id="frequency" x-model="frequency" placeholder="e.g., TID (3x daily)" class="w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); --tw-ring-color: var(--primary);">
                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                        <template x-for="f in frequencyOptions" :key="f">
                            <button type="button" @click="frequency = f" class="px-2 py-0.5 rounded-full text-[11px] transition hover:opacity-80" :class="frequency === f ? 'bg-[var(--primary)] text-white' : 'bg-teal-soft text-[var(--primary)]'" x-text="f"></button>
                        </template>
                    </div>
                </div>
                <div>
                    <label for="duration" class="block text-sm font-medium mb-2" style="color: var(--ink-muted);">Duration <span class="text-[var(--danger)]" aria-hidden="true">*</span></label>
                    <input type="text" id="duration" x-model="duration" placeholder="e.g., 7 days" class="w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); --tw-ring-color: var(--primary);">
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
                    <input type="number" id="quantity" x-model="quantity" placeholder="e.g., 21" min="1" class="w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); --tw-ring-color: var(--primary);">
                </div>
                <div>
                    <label for="instructions" class="block text-sm font-medium mb-2" style="color: var(--ink-muted);">Instructions</label>
                    <input type="text" id="instructions" x-model="instructions" placeholder="e.g., After meals" class="w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); --tw-ring-color: var(--primary);">
                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                        <template x-for="i in instructionOptions" :key="i">
                            <button type="button" @click="instructions = i" class="px-2 py-0.5 rounded-full text-[11px] transition hover:opacity-80" :class="instructions === i ? 'bg-[var(--primary)] text-white' : 'bg-teal-soft text-[var(--primary)]'" x-text="i"></button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
            <button type="button" class="px-4 py-2 rounded-lg text-sm font-medium transition" style="background: var(--border); color: var(--ink);" onclick="closePrescriptionModal()">Cancel</button>
            <button type="button" id="prescriptionSubmitBtn" class="px-4 py-2 rounded-lg text-white text-sm font-medium transition" style="background: var(--primary);" onclick="addPrescription()">Add</button>
        </div>
    </div>
</div>
@endpush

<script>
const CONSULTATION_ID = {{ $consultation->id }};
const CSRF_TOKEN = document.querySelector('input[name="_token"]').value;
let editMode = { diagnosis: false, prescription: false };
let editId = { diagnosis: null, prescription: null };

function isSafeFetchHandledError(err) {
    const message = (err && err.message) || '';
    return message === 'Session expired'
        || message === 'Forbidden'
        || message === 'Not found'
        || message.indexOf('Server error') === 0;
}

function editDiagnosisSearch() {
    return {
        query: '',
        results: [],
        selectedId: null,
        selectedText: '',
        searching: false,
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
    };
}

const FREQUENCY_OPTIONS = ['OD (once daily)', 'BID (2x daily)', 'TID (3x daily)', 'QID (4x daily)', 'Every 4 hours', 'Every 6 hours', 'Every 8 hours', 'PRN (as needed)', 'At bedtime'];
const DURATION_OPTIONS = ['1 day', '3 days', '5 days', '7 days', '10 days', '14 days', '1 month'];
const INSTRUCTION_OPTIONS = ['Before meals', 'After meals', 'With food', 'On empty stomach', 'At bedtime'];
const QUICK_ROUTES = ['PO', 'IV', 'IM', 'Topical', 'Inhalation'];

function editMedicineSearch() {
    return {
        query: '',
        results: [],
        selectedId: null,
        selectedText: '',
        selectedForm: '',
        searching: false,
        dosage: '',
        route: '',
        frequency: '',
        duration: '',
        quantity: '',
        instructions: '',
        frequencyOptions: FREQUENCY_OPTIONS,
        durationOptions: DURATION_OPTIONS,
        instructionOptions: INSTRUCTION_OPTIONS,
        quickRoutes: QUICK_ROUTES,
        get dosageUnits() {
            const form = (this.selectedForm || '').toLowerCase();
            if (form.includes('cap')) return ['1 cap', '2 caps'];
            if (form.includes('syr') || form.includes('susp') || form.includes('drop') || form.includes('elix')) return ['5 mL', '10 mL'];
            if (form.includes('amp') || form.includes('vial') || form.includes('inj')) return ['1 amp', '1 vial'];
            if (form.includes('sachet') || form.includes('powder') || form.includes('granule')) return ['1 sachet'];
            if (form.includes('cream') || form.includes('oint') || form.includes('lotion') || form.includes('gel')) return ['Apply thinly', 'Apply generously'];
            if (form.includes('spray') || form.includes('inhal')) return ['1 puff', '2 puffs'];
            return ['1 tab', '2 tabs'];
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
            this.route = detail.route || '';
            this.frequency = detail.frequency || '';
            this.duration = detail.duration || '';
            this.quantity = detail.quantity === null || detail.quantity === undefined ? '' : String(detail.quantity);
            this.instructions = detail.instructions || '';
        },
        resetForm() {
            this.preselect(null, '');
            this.dosage = '';
            this.route = '';
            this.frequency = '';
            this.duration = '';
            this.quantity = '';
            this.instructions = '';
        },
    };
}

function openDiagnosisModal() {
    editMode.diagnosis = false;
    editId.diagnosis = null;
    document.getElementById('diagnosisModalTitle').textContent = 'Add Diagnosis';
    document.getElementById('diagnosisSubmitBtn').textContent = 'Add';
    window.dispatchEvent(new CustomEvent('set-diagnosis-query', { detail: { query: '' } }));
    document.getElementById('diagnosisRemarks').value = '';
    showModal('diagnosisModal', 'diagnosisPanel');
}

function closeDiagnosisModal() {
    hideModal('diagnosisModal', 'diagnosisPanel');
    window.dispatchEvent(new CustomEvent('set-diagnosis-query', { detail: { query: '' } }));
    document.getElementById('diagnosisRemarks').value = '';
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
        Swal.fire({title: 'Missing diagnosis', text: 'Please enter a diagnosis name.', icon: 'warning', confirmButtonColor: 'var(--primary)'});
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
            'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(response => {
        if (!response.ok) return response.json().then(err => { throw err; });
        return response.json();
    })
    .then(() => {
        closeDiagnosisModal();
        location.reload();
    })
    .catch(err => {
        if (isSafeFetchHandledError(err)) return;
        const msg = err.message || (err.errors ? Object.values(err.errors).flat().join(', ') : 'Failed to save diagnosis.');
        Swal.fire({title: 'Error', text: msg, icon: 'error', confirmButtonColor: 'var(--primary)'});
    });
}

function editDiagnosis(id, name, remarks, lookupId) {
    editMode.diagnosis = true;
    editId.diagnosis = id;
    document.getElementById('diagnosisModalTitle').textContent = 'Edit Diagnosis';
    document.getElementById('diagnosisSubmitBtn').textContent = 'Update';
    window.dispatchEvent(new CustomEvent('preselect-diagnosis', { detail: { id: lookupId, text: name || '' } }));
    document.getElementById('diagnosisRemarks').value = remarks || '';
    showModal('diagnosisModal', 'diagnosisPanel');
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
            confirmButtonColor: 'var(--primary)'
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
        instructions
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
            'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(response => {
        if (!response.ok) return response.json().then(err => { throw err; });
        return response.json();
    })
    .then(() => {
        closePrescriptionModal();
        location.reload();
    })
    .catch(err => {
        if (isSafeFetchHandledError(err)) return;
        const msg = err.message || (err.errors ? Object.values(err.errors).flat().join(', ') : 'Failed to save prescription.');
        Swal.fire({title: 'Error', text: msg, icon: 'error', confirmButtonColor: 'var(--primary)'});
    });
}

function editPrescription(id, medicineName, dosage, route, frequency, duration, quantity, instructions, medicineId) {
    editMode.prescription = true;
    editId.prescription = id;
    document.getElementById('prescriptionModalTitle').textContent = 'Edit Prescription';
    document.getElementById('prescriptionSubmitBtn').textContent = 'Update';
    window.dispatchEvent(new CustomEvent('fill-prescription', {
        detail: {
            medicine_id: medicineId,
            medicine_name: medicineName,
            dosage,
            route,
            frequency,
            duration,
            quantity,
            instructions,
        }
    }));
    showModal('prescriptionModal', 'prescriptionPanel');
}

function deleteDiagnosis(id) {
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
        safeFetch(`/consultations/${CONSULTATION_ID}/diagnoses/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (response.ok) {
                location.reload();
            } else {
                Swal.fire({title: 'Error', text: 'Failed to delete diagnosis.', icon: 'error', confirmButtonColor: 'var(--primary)'});
            }
        })
        .catch(err => {
            if (isSafeFetchHandledError(err)) return;
            Swal.fire({title: 'Error', text: 'Error deleting diagnosis.', icon: 'error', confirmButtonColor: 'var(--primary)'});
        });
    });
}

function deletePrescription(id) {
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
        safeFetch(`/consultations/${CONSULTATION_ID}/prescriptions/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (response.ok) {
                location.reload();
            } else {
                Swal.fire({title: 'Error', text: 'Failed to delete prescription.', icon: 'error', confirmButtonColor: 'var(--primary)'});
            }
        })
        .catch(err => {
            if (isSafeFetchHandledError(err)) return;
            Swal.fire({title: 'Error', text: 'Error deleting prescription.', icon: 'error', confirmButtonColor: 'var(--primary)'});
        });
    });
}

document.getElementById('diagnosisModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeDiagnosisModal();
});

document.getElementById('prescriptionModal')?.addEventListener('click', function(e) {
    if (e.target === this) closePrescriptionModal();
});
</script>

@endsection
