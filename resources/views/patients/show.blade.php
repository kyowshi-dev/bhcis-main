@extends('layouts.app')

@section('title', 'Patient Profile')

@section('content')
<x-flash />

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 lg:gap-6">
    <div class="md:col-span-1 space-y-4 lg:space-y-6">
        <div class="relative bg-surface p-4 lg:p-6 rounded-xl lg:rounded-lg shadow-sm border border-border"
             x-data="{ editing: {{ $errors->any() ? 'true' : 'false' }} }">
            @if (auth()->user()?->hasPermission('patients'))
                <button type="button"
                        @click="editing = !editing"
                        :aria-expanded="editing.toString()"
                        aria-controls="patient-info-panel"
                        aria-label="Edit patient information"
                        :class="editing ? 'bg-teal-soft text-primary' : 'text-ink-muted hover:bg-black/5'"
                        class="absolute top-3 right-3 inline-flex items-center justify-center rounded-lg p-2 transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-blue">
                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                </button>
            @endif
            <div class="text-center mb-3 lg:mb-4">
                <div class="w-16 h-16 lg:w-20 lg:h-20 rounded-full flex items-center justify-center text-2xl lg:text-3xl font-bold mx-auto" style="background: var(--teal-soft); color: var(--primary);">
                    {{ $patient->initials }}
                </div>
                <h2 class="text-lg lg:text-xl font-bold mt-2">{{ fullName($patient->last_name, $patient->first_name, $patient->middle_name, $patient->suffix) }}</h2>
                <p class="text-ink-muted text-xs lg:text-sm">ID: {{ $patient->patient_code }}</p>
            </div>

            <hr class="my-3 lg:my-4">

            <div id="patient-info-panel">
                <div x-show="!editing" class="space-y-2 lg:space-y-3 text-xs lg:text-sm">
                    <div class="flex justify-between">
                        <span class="text-ink-muted">Age / Sex:</span>
                        <span class="font-medium">{{ $patient->age }} y/o / {{ $patient->sex }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-ink-muted">Birthdate:</span>
                        <span class="font-medium">{{ $patient->date_of_birth?->format('M j, Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-ink-muted">Blood Type:</span>
                        <span class="font-medium">{{ $patient->blood_type ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between items-start">
                        <span class="text-ink-muted">Mother's Name:</span>
                        <span class="font-medium text-right">{{ ucwords($patient->mother_name) }}</span>
                    </div>
                    <div class="flex justify-between items-start">
                        <span class="text-ink-muted">Spouse's Name:</span>
                        <span class="font-medium text-right">{{ ucwords($patient->spouse_name) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-ink-muted">Relationship:</span>
                        <span class="font-medium">{{ $patient->family_relationship }}</span>
                    </div>
                    <div class="flex justify-between items-start">
                        <span class="text-ink-muted">Residential Address:</span>
                        <span class="font-medium text-right">{{ $patient->residential_address }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-ink-muted">Zone:</span>
                        <span class="font-medium">Zone {{ $patient->zone_id }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-ink-muted">Civil Status:</span>
                        <span class="font-medium">{{ $patient->civil_status }}</span>
                    </div>
                </div>

                @if (auth()->user()?->hasPermission('patients'))
                    <form x-show="editing" x-cloak method="POST" action="{{ route('patients.update', $patient->id) }}"
                          class="space-y-3" aria-label="Edit patient information">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 gap-3">
                        <x-field name="first_name" label="First Name" required>
                            <x-slot:control>
                                <input type="text" name="first_name" id="first_name" required maxlength="255"
                                       value="{{ old('first_name', $patient->first_name) }}"
                                       class="w-full rounded-lg border border-border px-3 py-2 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-accent-blue">
                            </x-slot:control>
                        </x-field>

                        <x-field name="middle_name" label="Middle Name">
                            <x-slot:control>
                                <input type="text" name="middle_name" id="middle_name" maxlength="255"
                                       value="{{ old('middle_name', $patient->middle_name) }}"
                                       class="w-full rounded-lg border border-border px-3 py-2 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-accent-blue">
                            </x-slot:control>
                        </x-field>

                        <x-field name="last_name" label="Last Name" required>
                            <x-slot:control>
                                <input type="text" name="last_name" id="last_name" required maxlength="255"
                                       value="{{ old('last_name', $patient->last_name) }}"
                                       class="w-full rounded-lg border border-border px-3 py-2 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-accent-blue">
                            </x-slot:control>
                        </x-field>

                        <x-field name="suffix" label="Suffix">
                            <x-slot:control>
                                <input type="text" name="suffix" id="suffix" maxlength="50"
                                       value="{{ old('suffix', $patient->suffix) }}"
                                       class="w-full rounded-lg border border-border px-3 py-2 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-accent-blue">
                            </x-slot:control>
                        </x-field>

                        <x-field name="sex" label="Sex" required>
                            <x-slot:control>
                                <select name="sex" id="sex" required
                                        class="w-full rounded-lg border border-border px-3 py-2 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-accent-blue">
                                    <option value="Male" @selected(old('sex', $patient->sex) === 'Male')>Male</option>
                                    <option value="Female" @selected(old('sex', $patient->sex) === 'Female')>Female</option>
                                </select>
                            </x-slot:control>
                        </x-field>

                        <x-field name="date_of_birth" label="Birthdate" required>
                            <x-slot:control>
                                <input type="date" name="date_of_birth" id="date_of_birth" required
                                       max="{{ now()->toDateString() }}"
                                       value="{{ old('date_of_birth', $patient->date_of_birth?->format('Y-m-d')) }}"
                                       class="w-full rounded-lg border border-border px-3 py-2 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-accent-blue">
                            </x-slot:control>
                        </x-field>

                        <x-field name="blood_type" label="Blood Type">
                            <x-slot:control>
                                <select name="blood_type" id="blood_type"
                                        class="w-full rounded-lg border border-border px-3 py-2 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-accent-blue">
                                    <option value="">Not set</option>
                                    @foreach (['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $type)
                                        <option value="{{ $type }}" @selected(old('blood_type', $patient->blood_type) === $type)>{{ $type }}</option>
                                    @endforeach
                                </select>
                            </x-slot:control>
                        </x-field>

                        <x-field name="civil_status" label="Civil Status" required>
                            <x-slot:control>
                                @php
                                    $civilStatusOptions = ['Single (Walang Asawa)', 'Married (May Asawa)', 'Annulled (Hiwalay)', 'Widow/er (Balo)', 'Separated (Hiwalay)', 'Co-Habitation (Paninirahang magkasama)'];
                                    $selectedCivilStatus = old('civil_status', $patient->civil_status);
                                    if (is_string($selectedCivilStatus) && $selectedCivilStatus !== '' && ! in_array($selectedCivilStatus, $civilStatusOptions, true)) {
                                        $civilStatusOptions[] = $selectedCivilStatus;
                                    }
                                @endphp
                                <select name="civil_status" id="civil_status" required
                                        class="w-full rounded-lg border border-border px-3 py-2 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-accent-blue">
                                    @foreach ($civilStatusOptions as $status)
                                        <option value="{{ $status }}" @selected($selectedCivilStatus === $status)>{{ $status }}</option>
                                    @endforeach
                                </select>
                            </x-slot:control>
                        </x-field>

                        <x-field name="mother_name" label="Mother's Name">
                            <x-slot:control>
                                <input type="text" name="mother_name" id="mother_name" maxlength="255"
                                       value="{{ old('mother_name', $patient->mother_name) }}"
                                       class="w-full rounded-lg border border-border px-3 py-2 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-accent-blue">
                            </x-slot:control>
                        </x-field>

                        <x-field name="spouse_name" label="Spouse's Name">
                            <x-slot:control>
                                <input type="text" name="spouse_name" id="spouse_name" maxlength="255"
                                       value="{{ old('spouse_name', $patient->spouse_name) }}"
                                       class="w-full rounded-lg border border-border px-3 py-2 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-accent-blue">
                            </x-slot:control>
                        </x-field>

                        <x-field name="family_relationship" label="Relationship">
                            <x-slot:control>
                                <select name="family_relationship" id="family_relationship"
                                        class="w-full rounded-lg border border-border px-3 py-2 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-accent-blue">
                                    <option value="">Not set</option>
                                    @foreach (\App\Models\Patient::FAMILY_RELATIONSHIP_OPTIONS as $relationship)
                                        <option value="{{ $relationship }}" @selected(old('family_relationship', $patient->family_relationship) === $relationship)>{{ $relationship }}</option>
                                    @endforeach
                                </select>
                            </x-slot:control>
                        </x-field>

                        <x-field name="residential_address" label="Residential Address">
                            <x-slot:control>
                                <input type="text" name="residential_address" id="residential_address" maxlength="255"
                                       value="{{ old('residential_address', $patient->residential_address) }}"
                                       class="w-full rounded-lg border border-border px-3 py-2 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-accent-blue">
                            </x-slot:control>
                        </x-field>

                        <x-field name="philhealth_no" label="PhilHealth Number">
                            <x-slot:control>
                                <input type="text" name="philhealth_no" id="philhealth_no" maxlength="50"
                                       value="{{ old('philhealth_no', $patient->philhealth_no) }}"
                                       class="w-full rounded-lg border border-border px-3 py-2 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-accent-blue">
                            </x-slot:control>
                        </x-field>

                        <x-field name="is_philhealth_member" label="PhilHealth Member">
                            <x-slot:control>
                                <select name="is_philhealth_member" id="is_philhealth_member"
                                        class="w-full rounded-lg border border-border px-3 py-2 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-accent-blue">
                                    <option value="y" @selected(old('is_philhealth_member', $patient->is_philhealth_member) === 'y')>Yes</option>
                                    <option value="n" @selected(old('is_philhealth_member', $patient->is_philhealth_member) === 'n')>No</option>
                                </select>
                            </x-slot:control>
                        </x-field>

                        <x-field name="is_pcb_member" label="PCB Member">
                            <x-slot:control>
                                <select name="is_pcb_member" id="is_pcb_member"
                                        class="w-full rounded-lg border border-border px-3 py-2 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-accent-blue">
                                    <option value="y" @selected(old('is_pcb_member', $patient->is_pcb_member) === 'y')>Yes</option>
                                    <option value="n" @selected(old('is_pcb_member', $patient->is_pcb_member) === 'n')>No</option>
                                </select>
                            </x-slot:control>
                        </x-field>

                        <x-field name="has_4ps" label="4Ps Beneficiary">
                            <x-slot:control>
                                <select name="has_4ps" id="has_4ps"
                                        class="w-full rounded-lg border border-border px-3 py-2 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-accent-blue">
                                    <option value="y" @selected(old('has_4ps', $patient->has_4ps) === 'y')>Yes</option>
                                    <option value="n" @selected(old('has_4ps', $patient->has_4ps) === 'n')>No</option>
                                </select>
                            </x-slot:control>
                        </x-field>

                        <x-field name="has_nhts" label="NHTS Beneficiary">
                            <x-slot:control>
                                <select name="has_nhts" id="has_nhts"
                                        class="w-full rounded-lg border border-border px-3 py-2 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-accent-blue">
                                    <option value="y" @selected(old('has_nhts', $patient->has_nhts) === 'y')>Yes</option>
                                    <option value="n" @selected(old('has_nhts', $patient->has_nhts) === 'n')>No</option>
                                </select>
                            </x-slot:control>
                        </x-field>
                    </div>

                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button" @click="editing = false"
                                class="rounded-lg border border-border px-4 py-2 text-sm font-semibold text-ink-muted transition hover:bg-black/[0.03]">
                            Cancel
                        </button>
                        <button type="submit"
                                class="rounded-lg px-4 py-2 text-sm font-semibold text-white transition hover:shadow-md"
                                style="background: var(--primary);">
                            <i class="fa-solid fa-save mr-1" aria-hidden="true"></i> Save
                        </button>
                    </div>
                    </form>
                @endif
            </div>

            <div class="mt-4 lg:mt-6 pt-3 lg:pt-4 border-t">
                <h4 class="text-xs font-bold text-ink-subtle uppercase mb-2">Programs</h4>
                <div class="flex flex-wrap gap-2">
                    @if($patient->has_4ps)
                        <span class="px-2 py-1 bg-amber-soft text-amber text-xs rounded-full">4Ps Member</span>
                    @endif
                    @if($patient->has_nhts)
                        <span class="px-2 py-1 bg-accent-blue-soft text-accent-blue text-xs rounded-full">NHTS</span>
                    @endif
                    @if(!$patient->has_4ps && !$patient->has_nhts)
                        <span class="text-ink-subtle text-xs italic">No active programs</span>
                    @endif
                </div>
            </div>

            <div class="mt-4 lg:mt-6 pt-3 lg:pt-4 border-t">
                <h4 class="text-xs font-bold text-ink-subtle uppercase mb-2">Membership</h4>
                <div class="space-y-2 text-xs lg:text-sm text-ink-muted">
                    <div class="flex justify-between">
                        <span>PhilHealth Member</span>
                        <span class="font-medium">{{ $patient->is_philhealth_member === 'y' ? 'Yes' : 'No' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>PhilHealth No.</span>
                        <span class="font-medium">{{ $patient->philhealth_no ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Category</span>
                        <span class="font-medium">{{ $patient->membership_category ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Status Type</span>
                        <span class="font-medium">{{ $patient->status_type ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>PCB Member</span>
                        <span class="font-medium">{{ $patient->is_pcb_member === 'y' ? 'Yes' : 'No' }}</span>
                    </div>
                </div>
            </div>

            <div class="mt-4 lg:mt-6 pt-3 lg:pt-4 border-t">
                <h4 class="text-xs font-bold text-ink-subtle uppercase mb-2">Maternal Care</h4>
                @if (auth()->user()?->hasPermission('maternal') && $patient->sex === 'Female' && $patient->age >= 15 && $patient->age <= 49)
                    @if ($patient->activePregnancy)
                        <p class="text-xs lg:text-sm text-ink-muted mb-1">
                            Active pregnancy · EDC <span class="font-semibold" style="color: var(--ink);">{{ $patient->activePregnancy->edc?->format('M d, Y') }}</span>
                        </p>
                        <p class="text-xs lg:text-sm text-ink-muted mb-2">
                            {{ $patient->activePregnancy->visits_count }} prenatal visit(s) recorded.
                        </p>
                    @else
                        <p class="text-xs lg:text-sm text-ink-muted mb-2">No active pregnancy recorded.</p>
                    @endif
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('maternal.prenatal.patient', $patient->id) }}" class="inline-flex items-center justify-center flex-1 px-3 py-2 rounded-lg text-xs lg:text-sm font-medium text-teal-700 bg-teal-50 hover:bg-teal-100 transition">
                            <i class="fa-solid fa-baby-carriage mr-1.5" aria-hidden="true"></i> Prenatal
                        </a>
                        <a href="{{ route('maternal.postnatal.patient', $patient->id) }}" class="inline-flex items-center justify-center flex-1 px-3 py-2 rounded-lg text-xs lg:text-sm font-medium text-teal-700 bg-teal-50 hover:bg-teal-100 transition">
                            <i class="fa-solid fa-child-reaching mr-1.5" aria-hidden="true"></i> Postnatal
                        </a>
                        <a href="{{ route('maternal.family-planning.patient', $patient->id) }}" class="inline-flex items-center justify-center flex-1 px-3 py-2 rounded-lg text-xs lg:text-sm font-medium text-teal-700 bg-teal-50 hover:bg-teal-100 transition">
                            <i class="fa-solid fa-hand-holding-heart mr-1.5" aria-hidden="true"></i> Family Planning
                        </a>
                    </div>
                @else
                    <p class="text-xs lg:text-sm text-ink-muted mb-2">Maternal care programs are not applicable for this patient.</p>
                @endif
            </div>

            @if (auth()->user()?->hasPermission('immunizations'))
                <div class="mt-4 lg:mt-6 pt-3 lg:pt-4 border-t">
                    <h4 class="text-xs font-bold text-ink-subtle uppercase mb-2">Immunization</h4>
                    <p class="text-xs lg:text-sm text-ink-muted mb-2">{{ $patient->immunization_records_count }} dose(s) recorded.</p>
                    <a href="{{ route('immunizations.patient', $patient->id) }}" class="inline-flex items-center justify-center w-full px-3 py-2 rounded-lg text-xs lg:text-sm font-medium bg-teal-50 text-teal-700 hover:bg-teal-100 transition">
                        View / Add Immunization
                    </a>
                </div>
            @endif

            @if ($patient->death)
                <div class="mt-4 lg:mt-6 pt-3 lg:pt-4 border-t">
                    <h4 class="text-xs font-bold text-ink-subtle uppercase mb-2 text-red-600">Death Record</h4>
                    <dl class="space-y-2 text-xs lg:text-sm">
                        <div class="flex justify-between gap-3">
                            <dt style="color: var(--ink-muted);">Date of Death</dt>
                            <dd class="font-semibold text-red-600">{{ $patient->death->date_of_death->format('M d, Y') }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt style="color: var(--ink-muted);">Place of Death</dt>
                            <dd class="font-semibold" style="color: var(--ink);">{{ $patient->death->place_of_death }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt style="color: var(--ink-muted);">Cause</dt>
                            <dd class="font-semibold" style="color: var(--ink);">
                                @if ($patient->death->causeDiagnosis)
                                    {{ $patient->death->causeDiagnosis->diagnosis_code }} - {{ $patient->death->causeDiagnosis->diagnosis_name }}
                                @elseif ($patient->death->cause_text)
                                    {{ $patient->death->cause_text }}
                                @else
                                    <span class="text-ink-muted">Not specified</span>
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt style="color: var(--ink-muted);">Classification</dt>
                            <dd class="font-semibold" style="color: var(--ink);">
                                @php
                                    $flags = [];
                                    if ($patient->death->maternal_death) $flags[] = 'Maternal';
                                    if ($patient->death->perinatal_death) $flags[] = 'Perinatal';
                                    if ($patient->death->neonatal_death) $flags[] = 'Neonatal';
                                    if ($patient->death->infant_death) $flags[] = 'Infant';
                                    if ($patient->death->under_five_death) $flags[] = 'Under-5';
                                @endphp
                                @if (!empty($flags))
                                    {{ implode(', ', $flags) }} death
                                @else
                                    <span class="text-ink-muted">None</span>
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt style="color: var(--ink-muted);">Recorded By</dt>
                            <dd class="font-semibold" style="color: var(--ink);">
                                @if ($patient->death->recordedBy)
                                    {{ $patient->death->recordedBy->first_name }} {{ $patient->death->recordedBy->last_name }}
                                @else
                                    <span class="text-ink-muted">Unknown</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                    <div class="mt-3">
                        <a href="{{ route('deaths.show', $patient->death->id) }}" class="inline-flex items-center justify-center w-full px-3 py-2 rounded-lg text-xs lg:text-sm font-medium bg-red-50 text-red-700 hover:bg-red-100 transition">
                            View Death Record
                        </a>
                    </div>
                </div>
            @endif

            @if (auth()->user()?->hasPermission('patients_destroy'))
                <div class="mt-4 lg:mt-6 pt-3 lg:pt-4 border-t">
                    <h4 class="text-xs font-bold text-ink-subtle uppercase mb-2">Data Management</h4>
                    <div class="space-y-2">
                        <x-destructive-form
                            action="{{ route('patients.destroy', $patient->id) }}"
                            message="Archive this patient? They will be moved to trash and hidden from the main list."
                            confirmText="Archive"
                            class="w-full"
                        >
                            <button type="submit" class="inline-flex items-center justify-center w-full px-3 py-2 rounded-lg text-xs lg:text-sm font-medium transition" style="background: var(--amber-soft); color: var(--amber);">
                                <i class="fa-solid fa-box-archive mr-1.5" aria-hidden="true"></i> Archive Patient
                            </button>
                        </x-destructive-form>
                        <x-destructive-form
                            action="{{ route('patients.forceDestroy', $patient->id) }}"
                            message="PERMANENTLY delete this patient? This cannot be undone. All their records will be lost forever."
                            confirmText="Permanently Delete"
                            class="w-full"
                        >
                            <button type="submit" class="inline-flex items-center justify-center w-full px-3 py-2 rounded-lg text-xs lg:text-sm font-medium transition" style="background: var(--danger-soft, #fef2f2); color: var(--danger);">
                                <i class="fa-solid fa-trash-can mr-1.5" aria-hidden="true"></i> Delete Permanently
                            </button>
                        </x-destructive-form>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="md:col-span-2">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3 lg:mb-4">
            <h2 class="text-lg lg:text-xl font-bold text-ink">Consultation History</h2>
            <button type="button" onclick="openConsultationCreateModal({{ $patient->id }})" class="inline-flex items-center justify-center px-4 py-2 rounded-xl shadow-sm text-xs lg:text-sm font-medium text-white bg-[var(--primary)] hover:bg-[var(--primary-light)] transition">
                + New Consultation
            </button>
        </div>

        @if($history->isEmpty())
            <div class="bg-surface p-6 lg:p-8 rounded-xl lg:rounded-lg shadow-sm border border-dashed border-border text-center">
                <p class="text-ink-subtle mb-2 text-sm">No medical records found for this patient.</p>
                <p class="text-xs lg:text-sm text-ink-muted">Click "New Consultation" to create the first record.</p>
            </div>
        @else
            <div class="bg-surface rounded-xl lg:rounded-lg shadow-sm border border-border overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs lg:text-sm text-left">
                        <thead class="bg-teal-soft text-ink-muted font-medium border-b">
                            <tr>
                                <th class="px-2 lg:px-4 py-2 lg:py-3 whitespace-nowrap">Date</th>
                                <th class="px-2 lg:px-4 py-2 lg:py-3 whitespace-nowrap">Complaint</th>
                                <th class="px-2 lg:px-4 py-2 lg:py-3 whitespace-nowrap hidden sm:table-cell">Attended By</th>
                                <th class="px-2 lg:px-4 py-2 lg:py-3 whitespace-nowrap">Status</th>
                                <th class="px-2 lg:px-4 py-2 lg:py-3 whitespace-nowrap"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach($history as $record)
                            <tr class="hover:bg-teal-soft">
                                <td class="px-2 lg:px-4 py-2 lg:py-3 whitespace-nowrap">{{ $record->created_at->format('M d, Y') }}</td>
                                <td class="px-2 lg:px-4 py-2 lg:py-3 font-medium text-ink">
                                    <div>{{ $record->complaint_name ?? 'General Checkup' }}</div>
                                    <div class="text-xs text-ink-muted sm:hidden">{{ $record->worker_label }}</div>
                                </td>
                                <td class="px-2 lg:px-4 py-2 lg:py-3 hidden sm:table-cell">{{ $record->worker_label }}</td>
                                <td class="px-2 lg:px-4 py-2 lg:py-3">
                                    <span class="px-2 py-0.5 lg:py-1 rounded-full text-xs font-semibold" style="{{ $record->status_style }}">
                                        {{ $record->status_label }}
                                    </span>
                                </td>
                                <td class="px-2 lg:px-4 py-2 lg:py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('consultations.show', $record->id) }}" class="text-accent-blue hover:text-accent-blue font-semibold text-xs lg:text-sm">View</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
