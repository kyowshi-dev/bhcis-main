@extends('layouts.app')

@section('title', 'Enroll Patient')

@section('content')
@php
    $fieldStepMap = [
        'first_name' => 1, 'middle_name' => 1, 'last_name' => 1, 'suffix' => 1,
        'sex' => 1, 'date_of_birth' => 1, 'blood_type' => 1, 'birth_place' => 1,
        'mother_name' => 1, 'spouse_name' => 1, 'family_relationship' => 1,
        'household_id' => 2, 'household_member_id' => 2, 'create_new_household' => 2,
        'new_household_zone_id' => 2, 'new_household_head_first_name' => 2,
        'new_household_head_last_name' => 2, 'new_household_contact_number' => 2,
        'civil_status' => 3, 'educational_attainment' => 3, 'employment_status' => 3,
        'is_philhealth_member' => 3, 'philhealth_no' => 3, 'membership_category' => 3,
        'status_type' => 3, 'is_pcb_member' => 3, 'has_4ps' => 3, 'has_nhts' => 3,
    ];
    $errorSteps = [];
    foreach ($errors->keys() as $key) {
        if (isset($fieldStepMap[$key])) {
            $errorSteps[] = $fieldStepMap[$key];
        }
    }
    $initialStep = $errorSteps ? min($errorSteps) : 1;
    $preselectedMemberId = old('household_member_id', $selectedMemberId ?? '');
@endphp
<div class="max-w-4xl mx-auto space-y-2 lg:space-y-3" x-data='patientEnroll()'>
    <x-flash />

    <x-page-header
        title="Enroll Patient"
        subtitle="Register a new patient and create their initial health record."
        icon="fa-solid fa-user-plus"
    />

    {{-- Wizard step indicator --}}
    <div class="pb-1" aria-live="polite">
        <div class="flex items-center justify-between gap-3 mb-2">
            <p class="text-xs lg:text-sm font-semibold text-ink">Step <span x-text="currentStep"></span> of 3</p>
            <p class="text-xs text-ink-muted" x-text="stepName"></p>
        </div>
        <div class="h-1.5 rounded-full overflow-hidden bg-teal-soft">
            <div class="h-full rounded-full bg-primary transition-all duration-300 ease-out"
                 :style="`width: ${currentStep / 3 * 100}%`"></div>
        </div>
    </div>

    <form action="{{ route('patients.store') }}" method="POST" class="mt-0" novalidate>
        @csrf

        <div class="bg-surface rounded-xl lg:rounded-lg shadow-sm border border-border">

            {{-- Client-side step validation summary (shown for whichever step) --}}
            <div x-show="Object.keys(stepErrors).length > 0" x-cloak id="step-errors-alert" role="alert"
                 class="m-4 lg:m-6 mb-0 rounded-xl border p-3 lg:p-4" style="border-color: var(--danger); background: var(--danger-soft);">
                <p class="text-sm font-semibold text-danger">
                    <i class="fa-solid fa-circle-exclamation mr-1.5" aria-hidden="true"></i>Please complete the required fields
                </p>
                <ul class="mt-1.5 space-y-1 list-disc list-inside text-xs text-danger">
                    <template x-for="(msg, field) in stepErrors" :key="field">
                        <li x-text="msg"></li>
                    </template>
                </ul>
            </div>

            {{-- ================================================= STEP 1: Identity --}}
            <div x-show="currentStep === 1" x-cloak data-wizard-step="1"
                 class="p-4 lg:p-6 space-y-4 lg:space-y-5" :aria-hidden="currentStep !== 1">
                <h2 id="enroll-step-heading" tabindex="-1" class="text-sm lg:text-base font-extrabold flex items-center text-ink focus:outline-none">
                    <span class="mr-2"><i class="fas fa-user" aria-hidden="true"></i></span>
                    Personal Information
                </h2>

                {{-- Duplicate patient warning (non-blocking) --}}
                <div x-show="duplicateMatches.length > 0" x-cloak role="alert"
                     class="rounded-xl border p-3 lg:p-4" style="border-color: var(--amber); background: var(--amber-soft);">
                    <p class="text-sm font-semibold text-ink">
                        <i class="fa-solid fa-triangle-exclamation mr-1.5" aria-hidden="true"></i>Possible duplicate record
                    </p>
                    <p class="text-xs mt-1 text-ink-muted">A patient with the same name and birthdate may already exist. Review before continuing.</p>
                    <ul class="mt-2 space-y-1.5">
                        <template x-for="dup in duplicateMatches" :key="dup.id">
                            <li class="text-xs flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                <a :href="`/patients/${dup.id}`" target="_blank" rel="noopener"
                                   class="font-medium underline text-ink" x-text="dup.text"></a>
                                <span class="text-ink-muted" x-text="dup.subtext"></span>
                            </li>
                        </template>
                    </ul>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 lg:gap-4">
                    <x-field label="First Name" name="first_name" required>
                        <x-slot:control>
                            <input type="text" id="first_name" name="first_name" x-model="firstName"
                                   value="{{ old('first_name') }}" autocomplete="off" maxlength="50"
                                   class="w-full px-3 lg:px-4 py-2 rounded-xl border text-sm focus:ring-accent-blue focus:border-accent-blue @error('first_name') border-danger bg-danger-soft @else border-border @enderror">
                        </x-slot:control>
                    </x-field>

                    <x-field label="Middle Name" name="middle_name">
                        <x-slot:control>
                            <input type="text" id="middle_name" name="middle_name"
                                   value="{{ old('middle_name') }}" autocomplete="off" maxlength="50"
                                   class="w-full px-3 lg:px-4 py-2 rounded-xl border text-sm focus:ring-accent-blue focus:border-accent-blue @error('middle_name') border-danger bg-danger-soft @else border-border @enderror">
                        </x-slot:control>
                    </x-field>

                    <x-field label="Last Name" name="last_name" required>
                        <x-slot:control>
                            <input type="text" id="last_name" name="last_name" x-model="lastName"
                                   value="{{ old('last_name') }}" autocomplete="off" maxlength="50"
                                   class="w-full px-3 lg:px-4 py-2 rounded-xl border text-sm focus:ring-accent-blue focus:border-accent-blue @error('last_name') border-danger bg-danger-soft @else border-border @enderror">
                        </x-slot:control>
                    </x-field>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4">
                    <x-field label="Suffix" name="suffix">
                        <x-slot:control>
                            <input type="text" id="suffix" name="suffix" placeholder="Jr, III"
                                   value="{{ old('suffix') }}" x-model="suffix"
                                   class="w-full px-3 lg:px-4 py-2 rounded-xl border border-border text-sm focus:ring-accent-blue focus:border-accent-blue">
                        </x-slot:control>
                    </x-field>

                    <x-field label="Sex" name="sex" required>
                        <x-slot:control>
                            <select id="sex" name="sex" x-model="sex"
                                    class="w-full px-3 lg:px-4 py-2 rounded-xl border text-sm focus:ring-accent-blue focus:border-accent-blue @error('sex') border-danger @else border-border @enderror">
                                <option value="">Select sex</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </x-slot:control>
                    </x-field>

                    <x-field label="Birthdate" name="date_of_birth" required>
                        <x-slot:control>
                            <input type="date" id="date_of_birth" name="date_of_birth" x-model="dateOfBirth"
                                   value="{{ old('date_of_birth') }}" max="{{ date('Y-m-d') }}"
                                   class="w-full px-3 lg:px-4 py-2 rounded-xl border text-sm focus:ring-accent-blue focus:border-accent-blue @error('date_of_birth') border-danger bg-danger-soft @else border-border @enderror">
                        </x-slot:control>
                    </x-field>

                    <x-field label="Blood Type" name="blood_type" help="Optional">
                        <x-slot:control>
                            <select id="blood_type" name="blood_type"
                                    class="w-full px-3 lg:px-4 py-2 rounded-xl border border-border text-sm focus:ring-accent-blue focus:border-accent-blue">
                                <option value=""></option>
                                @foreach(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $type)
                                    <option value="{{ $type }}" @selected(old('blood_type') === $type)>{{ $type }}</option>
                                @endforeach
                            </select>
                        </x-slot:control>
                    </x-field>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 lg:gap-4">
                    <x-field label="Mother's Name" name="mother_name" required>
                        <x-slot:control>
                            <input type="text" id="mother_name" name="mother_name" placeholder="e.g. Maria Santos"
                                   value="{{ old('mother_name') }}" autocomplete="off"
                                   class="w-full px-3 lg:px-4 py-2 rounded-xl border text-sm focus:ring-accent-blue focus:border-accent-blue @error('mother_name') border-danger bg-danger-soft @else border-border @enderror">
                        </x-slot:control>
                    </x-field>

                    <x-field label="Partner / Spouse's Name" name="spouse_name" required>
                        <x-slot:control>
                            <input type="text" id="spouse_name" name="spouse_name"
                                   value="{{ old('spouse_name') }}" autocomplete="off"
                                   class="w-full px-3 lg:px-4 py-2 rounded-xl border text-sm focus:ring-accent-blue focus:border-accent-blue @error('spouse_name') border-danger bg-danger-soft @else border-border @enderror">
                        </x-slot:control>
                    </x-field>

                    <x-field label="Family Member (Posisyon sa Pamilya)" name="family_relationship" required>
                        <x-slot:control>
                            <select id="family_relationship" name="family_relationship"
                                    class="w-full px-3 lg:px-4 py-2 rounded-xl border text-sm focus:ring-accent-blue focus:border-accent-blue @error('family_relationship') border-danger @else border-border @enderror">
                                <option value="">Select relationship</option>
                                @foreach(\App\Models\Patient::FAMILY_RELATIONSHIP_OPTIONS as $relationship)
                                    <option value="{{ $relationship }}" @selected(old('family_relationship') === $relationship)>{{ $relationship }}</option>
                                @endforeach
                            </select>
                        </x-slot:control>
                    </x-field>
                </div>

                <x-field label="Birth Place" name="birth_place" help="City/Municipality, Province - pick a suggestion or type it as-is.">
                    <x-slot:control>
                        <div class="relative" @click.outside="birthPlaceOpen = false">
                            <input type="text" id="birth_place" name="birth_place" value="{{ old('birth_place') }}"
                                   x-model="birthPlace"
                                   @input.debounce.250ms="searchBirthPlace()"
                                   @focus="birthPlaceOpen = true"
                                   @keydown.escape="birthPlaceOpen = false"
                                   @click="birthPlaceOpen = true"
                                   :aria-expanded="birthPlaceOpen && birthPlaceResults.length > 0"
                                   aria-controls="birth_place_results"
                                   placeholder="City/Municipality, Province"
                                   autocomplete="off"
                                   class="w-full px-3 lg:px-4 py-2 rounded-xl border text-sm focus:ring-accent-blue focus:border-accent-blue @error('birth_place') border-danger bg-danger-soft @else border-border @enderror">

                            <div id="birth_place_results" role="listbox"
                                 x-show="birthPlaceOpen && birthPlaceResults.length > 0" x-cloak
                                 class="absolute z-50 w-full bg-surface mt-1 border border-border rounded-xl shadow-lg max-h-60 overflow-y-auto">
                                <ul>
                                    <template x-for="item in birthPlaceResults" :key="item.id">
                                        <li role="option" aria-selected="false"
                                            class="px-3 lg:px-4 py-2 lg:py-2.5 hover:bg-black/5 cursor-pointer border-b last:border-0 text-xs lg:text-sm"
                                            @click.prevent="selectBirthPlace(item)">
                                            <div class="font-medium text-ink" x-text="item.text"></div>
                                            <div class="text-xs text-ink-muted" x-text="item.subtext"></div>
                                        </li>
                                    </template>
                                </ul>
                            </div>

                            <div class="text-xs text-ink-muted mt-1" x-cloak
                                 x-show="birthPlaceOpen && birthPlace.trim().length > 1 && birthPlaceResults.length === 0 && !birthPlaceSearching">
                                No location found. Type the place as-is to save it.
                            </div>
                        </div>
                    </x-slot:control>
                </x-field>
            </div>

            {{-- ============================================ STEP 2: Household --}}
            <div x-show="currentStep === 2" x-cloak data-wizard-step="2"
                 class="p-4 lg:p-6 space-y-4 lg:space-y-5" :aria-hidden="currentStep !== 2">
                <h2 id="enroll-step-heading-2" tabindex="-1" class="text-sm lg:text-base font-extrabold flex items-center text-ink focus:outline-none">
                    <span class="mr-2"><i class="fas fa-home" aria-hidden="true"></i></span>
                    Household Information
                </h2>

                <input type="hidden" name="create_new_household" :value="creating ? 1 : 0">
                <input type="hidden" id="household_id" name="household_id" x-model="householdId">
                <input type="hidden" id="household_member_id" name="household_member_id" x-model="householdMemberId">

                {{-- Mode toggle --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <button type="button"
                            @click="enableAttaching()"
                            :aria-pressed="String(!creating)"
                            :class="creating ? 'border-border bg-teal-soft text-ink-muted' : 'border-primary bg-surface shadow-sm'"
                            class="rounded-2xl border p-4 text-left transition-all duration-200 hover:border-primary min-h-[44px]">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold">Attach to existing household</p>
                                <p class="text-xs text-ink-muted mt-1">Search by family name, zone, or contact.</p>
                            </div>
                            <span x-show="!creating" x-cloak class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold" style="background: var(--teal-soft); color: var(--primary);">Active</span>
                        </div>
                    </button>

                    <button type="button"
                            @click="enableCreating()"
                            :aria-pressed="String(creating)"
                            :class="creating ? 'border-primary bg-surface shadow-sm' : 'border-border bg-teal-soft text-ink-muted'"
                            class="rounded-2xl border p-4 text-left transition-all duration-200 hover:border-primary min-h-[44px]">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold">Create new household</p>
                                <p class="text-xs text-ink-muted mt-1">Add a new household record and attach this patient.</p>
                            </div>
                            <span x-show="creating" x-cloak class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold" style="background: var(--teal-soft); color: var(--primary);">Active</span>
                        </div>
                    </button>
                </div>

                {{-- Attach existing --}}
                <div x-show="!creating" x-cloak class="bg-teal-soft border border-border rounded-2xl p-4 space-y-4">
                    <div class="flex items-center gap-3 flex-wrap">
                        <input type="checkbox"
                               id="transient_unmapped"
                               x-model="isTransient"
                               @change="onTransientToggle()"
                               class="h-4 w-4 text-accent-blue focus:ring-accent-blue border-border rounded"
                               :disabled="transientHouseholdId === null">
                        <label for="transient_unmapped" class="text-xs lg:text-sm font-medium text-ink select-none">
                            Transient/Unmapped
                        </label>
                        <template x-if="transientHouseholdId === null">
                            <span class="text-xs text-danger">Transient household not found.</span>
                        </template>
                    </div>

                    <div>
                        <label for="household_search" class="block text-xs font-medium text-ink mb-1">
                            Select Household <span class="text-danger">*</span>
                        </label>
                        <p class="text-xs text-ink-muted mb-2">Search by family name, zone, or contact - pre-filled with the patient's last name.</p>

                        <div class="relative">
                            <input type="text" id="household_search"
                                   x-ref="householdSearch"
                                   x-model="query"
                                   @input.debounce.250ms="search()"
                                   @focus="dropdownOpen = true"
                                   @keydown.escape="dropdownOpen = false"
                                   @click="dropdownOpen = true"
                                   :disabled="isTransient"
                                   placeholder="Search household (family name / zone / contact)..."
                                   autocomplete="off"
                                   class="w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-xl border text-sm focus:ring-accent-blue focus:border-accent-blue @error('household_id') border-danger bg-danger-soft @else border-border @enderror disabled:opacity-60">

                            <div x-show="dropdownOpen && !isTransient && autocompleteResults.length > 0" x-cloak
                                 class="absolute z-50 w-full bg-surface mt-1 border border-border rounded-xl shadow-lg max-h-60 overflow-y-auto">
                                <ul>
                                    <template x-for="item in autocompleteResults" :key="item.id">
                                        <li class="px-3 lg:px-4 py-2 lg:py-2.5 hover:bg-black/5 cursor-pointer border-b last:border-0 text-xs lg:text-sm"
                                            @click.prevent="select(item)">
                                            <div class="font-medium text-ink" x-text="item.text"></div>
                                            <div class="text-xs text-ink-muted" x-text="item.subtext"></div>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>

                        <div class="text-xs text-ink-muted mt-1" x-cloak
                             x-show="!isTransient && query.length > 0 && autocompleteResults.length === 0 && !autocompleteLoading && !householdId">
                            No household found. Try a different search, or create a new household.
                        </div>
                        <div class="text-xs text-ink-muted mt-1" x-cloak x-show="autocompleteLoading">
                            Searching…
                        </div>

                        @error('household_id')
                            <p class="text-danger text-xs mt-1" data-auto-dismiss>{{ $message }}</p>
                        @enderror
                        @error('household_member_id')
                            <p class="text-danger text-xs mt-1" data-auto-dismiss>{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Household summary + resident picker --}}
                    <template x-if="householdId && !isTransient && !creating && householdInfo">
                        <div class="bg-surface border border-border rounded-xl p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-sm font-semibold text-ink">
                                    <i class="fa-solid fa-house-chimney mr-1.5 text-primary" aria-hidden="true"></i>Household selected
                                </h4>
                                <button type="button" @click="clearHouseholdSelection()"
                                        class="text-xs font-semibold text-danger hover:underline min-h-[44px] px-1">
                                    Change
                                </button>
                            </div>

                            <div class="grid grid-cols-2 lg:grid-cols-3 gap-2 text-xs text-ink-muted mb-3">
                                <div><span class="font-medium text-ink">Head:</span> <span x-text="householdInfo.display_name"></span></div>
                                <div><span class="font-medium text-ink">Zone:</span> <span x-text="householdInfo.zone_number"></span></div>
                                <div><span class="font-medium text-ink">Residents:</span> <span x-text="householdInfo.members.length"></span></div>
                            </div>

                            <div class="border-t pt-3 border-border">
                                <p class="text-xs font-medium text-ink mb-1">Select Resident <span class="text-danger">*</span></p>
                                <p class="text-xs text-ink-muted mb-2">Choose an existing resident or register as a new resident.</p>

                                <div class="space-y-2 max-h-56 overflow-y-auto">
                                    <template x-for="member in householdInfo.members" :key="member.id">
                                        <label class="flex items-center gap-3 p-2.5 rounded-lg border transition min-h-[44px]"
                                               :class="member.patient_linked ? 'opacity-60 cursor-not-allowed bg-black/[0.02]' : 'cursor-pointer hover:bg-black/5'"
                                               style="border-color: var(--border); background: var(--bg-surface);">
                                            <input type="radio"
                                                   name="member_picker"
                                                   :value="member.id"
                                                   x-model="householdMemberId"
                                                   :disabled="member.patient_linked"
                                                   @change="residentChosen = true"
                                                   class="h-4 w-4 text-accent-blue focus:ring-accent-blue border-border rounded disabled:cursor-not-allowed">
                                            <div class="flex-1 min-w-0">
                                                <div class="font-medium text-sm text-ink truncate" x-text="member.full_name"></div>
                                                <div class="text-xs flex flex-wrap gap-2 text-ink-muted">
                                                    <span x-text="member.sex"></span>
                                                    <span>|</span>
                                                    <span x-text="member.age + ' y/o'"></span>
                                                    <span>|</span>
                                                    <span x-text="member.relationship"></span>
                                                    <template x-if="member.patient_linked">
                                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold" style="background: var(--accent-blue-soft); color: var(--accent-blue);">
                                                            <i class="fa-solid fa-lock" aria-hidden="true"></i> Already a patient
                                                        </span>
                                                    </template>
                                                </div>
                                            </div>
                                        </label>
                                    </template>
                                </div>

                                <label class="flex items-center gap-3 p-2.5 rounded-lg border cursor-pointer hover:bg-black/5 transition mt-2 min-h-[44px] border-accent-blue bg-accent-blue-soft">
                                    <input type="radio"
                                           name="member_picker"
                                           value=""
                                           x-model="householdMemberId"
                                           @change="residentChosen = true"
                                           class="h-4 w-4 text-accent-blue focus:ring-accent-blue border-border rounded">
                                    <div class="flex-1">
                                        <div class="font-medium text-sm text-accent-blue">
                                            <i class="fa-solid fa-user-plus mr-1" aria-hidden="true"></i> Register as new resident
                                        </div>
                                        <div class="text-xs text-accent-blue">Create a new resident entry from the patient form data</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Create new household --}}
                <div x-show="creating" x-cloak class="bg-teal-soft border border-border rounded-2xl p-4 space-y-4">
                    <div>
                        <h4 class="text-sm font-semibold text-ink">New household details</h4>
                        <p class="text-xs text-ink-muted">Fill in the household information that will be created with this patient.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                        <x-field label="Zone" name="new_household_zone_id" required>
                            <x-slot:control>
                                <select id="new_household_zone_id" name="new_household_zone_id" x-model="newHouseholdZone"
                                        class="w-full px-3 lg:px-4 py-2 rounded-xl border text-sm focus:ring-accent-blue focus:border-accent-blue @error('new_household_zone_id') border-danger bg-danger-soft @else border-border @enderror">
                                    <option value="">Select zone</option>
                                    @foreach ($zones as $zone)
                                        <option value="{{ $zone->id }}">{{ $zone->zone_number }}</option>
                                    @endforeach
                                </select>
                            </x-slot:control>
                        </x-field>

                        <x-field label="Head First Name" name="new_household_head_first_name" required>
                            <x-slot:control>
                                <input type="text" id="new_household_head_first_name" name="new_household_head_first_name"
                                       x-model="newHouseholdHeadFirst" placeholder="First Name" autocomplete="off"
                                       class="w-full px-3 lg:px-4 py-2 rounded-xl border text-sm focus:ring-accent-blue focus:border-accent-blue @error('new_household_head_first_name') border-danger bg-danger-soft @else border-border @enderror">
                            </x-slot:control>
                        </x-field>

                        <x-field label="Head Last Name (Family)" name="new_household_head_last_name" required>
                            <x-slot:control>
                                <input type="text" id="new_household_head_last_name" name="new_household_head_last_name"
                                       x-model="newHouseholdHeadLast" placeholder="Last Name" autocomplete="off"
                                       class="w-full px-3 lg:px-4 py-2 rounded-xl border text-sm focus:ring-accent-blue focus:border-accent-blue @error('new_household_head_last_name') border-danger bg-danger-soft @else border-border @enderror">
                            </x-slot:control>
                        </x-field>

                        <x-field label="Contact Number" name="new_household_contact_number">
                            <x-slot:control>
                                <input type="text" id="new_household_contact_number" name="new_household_contact_number"
                                       value="{{ old('new_household_contact_number') }}" placeholder="e.g. 09XXXXXXXXX"
                                       class="w-full px-3 lg:px-4 py-2 rounded-xl border text-sm focus:ring-accent-blue focus:border-accent-blue @error('new_household_contact_number') border-danger bg-danger-soft @else border-border @enderror">
                            </x-slot:control>
                        </x-field>
                    </div>

                    <p class="text-xs text-ink-muted bg-accent-blue-soft border border-accent-blue/20 rounded-lg p-3">
                        <i class="fa-solid fa-circle-info mr-1" aria-hidden="true"></i>
                        A new household will be created and the patient will be added to it.
                    </p>
                </div>
            </div>

            {{-- ================================ STEP 3: Profile & review --}}
            <div x-show="currentStep === 3" x-cloak data-wizard-step="3"
                 class="p-4 lg:p-6 space-y-4 lg:space-y-5" :aria-hidden="currentStep !== 3">
                <h2 id="enroll-step-heading-3" tabindex="-1" class="text-sm lg:text-base font-extrabold flex items-center text-ink focus:outline-none">
                    <span class="mr-2"><i class="fas fa-peso-sign" aria-hidden="true"></i></span>
                    Profile &amp; Review
                </h2>

                {{-- Persistent duplicate reminder --}}
                <div x-show="duplicateMatches.length > 0" x-cloak role="alert"
                     class="rounded-xl border p-3 lg:p-4" style="border-color: var(--amber); background: var(--amber-soft);">
                    <p class="text-sm font-semibold text-ink">
                        <i class="fa-solid fa-triangle-exclamation mr-1.5" aria-hidden="true"></i>Possible duplicate record - review before saving
                    </p>
                    <ul class="mt-1.5 space-y-1 text-xs">
                        <template x-for="dup in duplicateMatches" :key="dup.id">
                            <li>
                                <a :href="`/patients/${dup.id}`" target="_blank" rel="noopener" class="underline font-medium text-ink" x-text="dup.text"></a>
                                <span class="text-ink-muted" x-text="dup.subtext"></span>
                            </li>
                        </template>
                    </ul>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 lg:gap-4">
                    <x-field label="Civil Status" name="civil_status">
                        <x-slot:control>
                            <select id="civil_status" name="civil_status"
                                    class="w-full px-3 lg:px-4 py-2 rounded-xl border border-border text-sm focus:ring-accent-blue focus:border-accent-blue">
                                @foreach(['Single (Walang Asawa)', 'Married (May Asawa)', 'Annulled (Hiwalay)', 'Widow/er (Balo)', 'Separated (Hiwalay)', 'Co-Habitation (Paninirahang magkasama)'] as $status)
                                    <option value="{{ $status }}" @selected(old('civil_status', 'Single (Walang Asawa)') === $status)>{{ $status }}</option>
                                @endforeach
                            </select>
                        </x-slot:control>
                    </x-field>

                    <x-field label="Educational Attainment" name="educational_attainment">
                        <x-slot:control>
                            <select id="educational_attainment" name="educational_attainment"
                                    class="w-full px-3 lg:px-4 py-2 rounded-xl border border-border text-sm focus:ring-accent-blue focus:border-accent-blue">
                                <option value="">Select level</option>
                                @foreach(['No Formal Education', 'Elementary', 'High School', 'College', 'Postgraduate', 'Vocational'] as $edu)
                                    <option value="{{ $edu }}" @selected(old('educational_attainment') === $edu)>{{ $edu }}</option>
                                @endforeach
                            </select>
                        </x-slot:control>
                    </x-field>

                    <x-field label="Employment Status" name="employment_status">
                        <x-slot:control>
                            <select id="employment_status" name="employment_status"
                                    class="w-full px-3 lg:px-4 py-2 rounded-xl border border-border text-sm focus:ring-accent-blue focus:border-accent-blue">
                                <option value="">Select employment status</option>
                                <option value="Student" @selected(old('employment_status') === 'Student')>Student (Estudyante)</option>
                                <option value="Employed" @selected(old('employment_status') === 'Employed')>Employed (May trabaho)</option>
                                <option value="Unknown" @selected(old('employment_status') === 'Unknown')>Unknown (Hindi malaman)</option>
                                <option value="Retired" @selected(old('employment_status') === 'Retired')>Retired (Retirado)</option>
                                <option value="Unemployed" @selected(old('employment_status') === 'Unemployed')>None/Unemployed (Walang Trabaho)</option>
                                <option value="Others" @selected(old('employment_status') === 'Others')>Others</option>
                            </select>
                        </x-slot:control>
                    </x-field>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 lg:gap-4">
                    <div>
                        <fieldset>
                            <legend class="block text-xs font-medium text-ink-muted mb-1">PhilHealth Member? <span class="text-danger">*</span></legend>
                            <div class="flex items-center gap-4">
                                <label class="inline-flex items-center gap-2 text-sm text-ink min-h-[44px] cursor-pointer">
                                    <input type="radio" name="is_philhealth_member" value="y" x-model="isPhilhealthMember"
                                           class="h-4 w-4 text-accent-blue border-border focus:ring-accent-blue">
                                    <span>Yes</span>
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-ink min-h-[44px] cursor-pointer">
                                    <input type="radio" name="is_philhealth_member" value="n" x-model="isPhilhealthMember"
                                           class="h-4 w-4 text-accent-blue border-border focus:ring-accent-blue">
                                    <span>No</span>
                                </label>
                            </div>
                        </fieldset>
                        @error('is_philhealth_member') <p class="text-danger text-xs mt-1" data-auto-dismiss>{{ $message }}</p> @enderror
                    </div>

                    <x-field label="PhilHealth Number" name="philhealth_no" help="Optional - leave blank if not a member">
                        <x-slot:control>
                            <input type="text" id="philhealth_no" name="philhealth_no" placeholder="12-123456789-0"
                                   value="{{ old('philhealth_no') }}"
                                   :disabled="isPhilhealthMember !== 'y'"
                                   class="w-full px-3 lg:px-4 py-2 rounded-xl border text-sm focus:ring-accent-blue focus:border-accent-blue disabled:opacity-50 disabled:bg-teal-soft disabled:cursor-not-allowed @error('philhealth_no') border-danger bg-danger-soft @else border-border @enderror">
                        </x-slot:control>
                    </x-field>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 lg:gap-4">
                    <x-field label="Membership Category" name="membership_category" help="Optional">
                        <x-slot:control>
                            <select id="membership_category" name="membership_category"
                                    :disabled="isPhilhealthMember !== 'y'"
                                    class="w-full px-3 lg:px-4 py-2 rounded-xl border text-sm focus:ring-accent-blue focus:border-accent-blue disabled:opacity-50 disabled:cursor-not-allowed @error('membership_category') border-danger @else border-border @enderror">
                                <option value="">Select category</option>
                                @foreach(\App\Models\Patient::PHILHEALTH_MEMBERSHIP_CATEGORIES as $category)
                                    <option value="{{ $category }}" @selected(old('membership_category') === $category)>{{ $category }}</option>
                                @endforeach
                            </select>
                        </x-slot:control>
                    </x-field>

                    <x-field label="PhilHealth Status" name="status_type" help="Optional">
                        <x-slot:control>
                            <select id="status_type" name="status_type"
                                    :disabled="isPhilhealthMember !== 'y'"
                                    class="w-full px-3 lg:px-4 py-2 rounded-xl border text-sm focus:ring-accent-blue focus:border-accent-blue disabled:opacity-50 disabled:cursor-not-allowed @error('status_type') border-danger @else border-border @enderror">
                                <option value="">Select status</option>
                                @foreach(\App\Models\Patient::PHILHEALTH_STATUS_TYPES as $status)
                                    <option value="{{ $status }}" @selected(old('status_type') === $status)>{{ $status }}</option>
                                @endforeach
                            </select>
                        </x-slot:control>
                    </x-field>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 lg:gap-4">
                    <div>
                        <fieldset>
                            <legend class="block text-xs font-medium text-ink-muted mb-1">PCB Member <span class="text-danger">*</span></legend>
                            <div class="flex items-center gap-4">
                                <label class="inline-flex items-center gap-2 text-sm text-ink min-h-[44px] cursor-pointer">
                                    <input type="radio" name="is_pcb_member" value="y" x-model="isPcbMember"
                                           class="h-4 w-4 text-accent-blue border-border focus:ring-accent-blue">
                                    <span>Yes</span>
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-ink min-h-[44px] cursor-pointer">
                                    <input type="radio" name="is_pcb_member" value="n" x-model="isPcbMember"
                                           class="h-4 w-4 text-accent-blue border-border focus:ring-accent-blue">
                                    <span>No</span>
                                </label>
                            </div>
                        </fieldset>
                        @error('is_pcb_member') <p class="text-danger text-xs mt-1" data-auto-dismiss>{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex flex-wrap gap-4 lg:gap-6 p-3 bg-teal-soft rounded-xl border border-border">
                    <div class="flex items-center min-h-[44px]">
                        <input type="checkbox" name="has_4ps" id="4ps" value="1" x-model="fourPs"
                               class="h-4 w-4 text-accent-blue focus:ring-accent-blue border-border rounded">
                        <label for="4ps" class="ml-2 block text-xs lg:text-sm text-900 font-medium cursor-pointer">4Ps Member</label>
                    </div>
                    <div class="flex items-center min-h-[44px]">
                        <input type="checkbox" name="has_nhts" id="nhts" value="1" x-model="nhts"
                               class="h-4 w-4 text-accent-blue focus:ring-accent-blue border-border rounded">
                        <label for="nhts" class="ml-2 block text-xs lg:text-sm text-900 font-medium cursor-pointer">NHTS / Indigent</label>
                    </div>
                </div>

                {{-- Review recap --}}
                <div class="rounded-xl border border-border overflow-hidden">
                    <div class="px-4 py-3 border-b border-border bg-surface-elevated">
                        <p class="text-xs font-semibold uppercase tracking-wider text-ink-muted">
                            <i class="fa-solid fa-clipboard-check mr-1.5" aria-hidden="true"></i>Review before saving
                        </p>
                    </div>
                    <div class="p-4 space-y-3 text-sm">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <p class="text-xs text-ink-muted">Patient</p>
                                <p class="font-semibold text-ink" x-text="reviewName"></p>
                            </div>
                            <div>
                                <p class="text-xs text-ink-muted">Sex &amp; age</p>
                                <p class="font-semibold text-ink"><span x-text="reviewSex"></span> · <span x-text="reviewAge"></span></p>
                            </div>
                            <div>
                                <p class="text-xs text-ink-muted">Birthdate</p>
                                <p class="font-semibold text-ink" x-text="dateOfBirth || '—'"></p>
                            </div>
                            <div>
                                <p class="text-xs text-ink-muted">Birth place</p>
                                <p class="font-semibold text-ink" x-text="birthPlace || '—'"></p>
                            </div>
                            <div class="sm:col-span-2">
                                <p class="text-xs text-ink-muted">Household</p>
                                <p class="font-semibold text-ink" x-text="reviewHousehold"></p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2 pt-2 border-t border-border">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-teal-soft text-primary" x-show="isPhilhealthMember === 'y'">
                                <i class="fa-solid fa-heart-pulse" aria-hidden="true"></i> PhilHealth member
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-teal-soft text-primary" x-show="isPcbMember === 'y'">
                                <i class="fa-solid fa-people-group" aria-hidden="true"></i> PCB member
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-teal-soft text-primary" x-show="fourPs">
                                <i class="fa-solid fa-hand-holding-heart" aria-hidden="true"></i> 4Ps
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-teal-soft text-primary" x-show="nhts">
                                <i class="fa-solid fa-house-crack" aria-hidden="true"></i> NHTS / Indigent
                            </span>
                            <span class="text-xs text-ink-muted"
                                  x-show="isPhilhealthMember !== 'y' && isPcbMember !== 'y' && !fourPs && !nhts">
                                No program memberships flagged
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sticky action bar --}}
            <div class="sticky bottom-0 z-40 px-4 py-3 border-t flex flex-col sm:flex-row sm:flex-wrap sm:justify-end gap-2 bg-surface/95 backdrop-blur border-border rounded-b-xl lg:rounded-b-lg">
                <a href="{{ route('patients.index') }}"
                   class="order-1 w-full sm:w-auto text-center px-4 lg:px-6 py-2.5 sm:py-2 rounded-xl border border-border text-sm font-medium text-ink-muted hover:bg-black/5 transition min-h-[44px]">
                    Cancel
                </a>

                <button type="button" x-show="currentStep > 1" @click="prevStep()"
                        class="order-2 w-full sm:w-auto px-4 lg:px-6 py-2.5 sm:py-2 rounded-xl border border-border text-sm font-semibold text-ink hover:bg-black/5 transition min-h-[44px]">
                    <i class="fa-solid fa-arrow-left mr-1.5" aria-hidden="true"></i>Back
                </button>

                <button type="button" x-show="currentStep < 3" @click="nextStep()"
                        class="order-3 w-full sm:w-auto px-5 lg:px-6 py-2.5 sm:py-2 rounded-xl text-white font-semibold text-sm shadow-md transition min-h-[44px]"
                        style="background: var(--primary);">
                    Continue<i class="fa-solid fa-arrow-right ml-1.5" aria-hidden="true"></i>
                </button>

                <button type="submit" x-show="currentStep === 3" x-cloak
                        class="order-3 w-full sm:w-auto px-5 lg:px-6 py-2.5 sm:py-2 rounded-xl text-white font-semibold text-sm shadow-md transition min-h-[44px]"
                        style="background: var(--primary);">
                    <i class="fa-solid fa-user-check mr-1.5" aria-hidden="true"></i>Save Patient Record
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    function patientEnroll() {
        return {
            currentStep: @js($initialStep),
            stepErrors: {},

            // Step 1 - identity
            firstName: @js(old('first_name', '')),
            lastName: @js(old('last_name', '')),
            suffix: @js(old('suffix', '')),
            sex: @js(old('sex', '')),
            dateOfBirth: @js(old('date_of_birth', '')),
            duplicateMatches: [],
            duplicateCheckTimer: null,

            // Birth place
            birthPlace: @js(old('birth_place', '')),
            birthPlaceResults: [],
            birthPlaceSearching: false,
            birthPlaceOpen: false,

            // Step 2 - household
            creating: @js((bool) old('create_new_household')),
            query: @js($selectedHousehold?->display_name ?? ''),
            householdId: @js($selectedHouseholdId ? (int) $selectedHouseholdId : null),
            householdMemberId: @js($preselectedMemberId),
            residentChosen: @js((bool) $preselectedMemberId),
            householdInfo: null,
            transientHouseholdId: @js($transientHouseholdId ?? null),
            transientHouseholdLabel: @js($transientHouseholdLabel ?? 'Transient/Unmapped'),
            isTransient: @js($selectedHouseholdId !== null && $transientHouseholdId !== null && (string) $selectedHouseholdId === (string) $transientHouseholdId),
            previousHouseholdId: null,
            previousQuery: null,
            dropdownOpen: false,
            autocompleteResults: [],
            autocompleteLoading: false,
            newHouseholdZone: @js((string) old('new_household_zone_id', '')),
            newHouseholdHeadFirst: @js(old('new_household_head_first_name', '')),
            newHouseholdHeadLast: @js(old('new_household_head_last_name', '')),
            householdPrefilled: false,

            // Step 3 - profile
            isPhilhealthMember: @js(old('is_philhealth_member', 'n')),
            isPcbMember: @js(old('is_pcb_member', 'n')),
            fourPs: @js((bool) old('has_4ps')),
            nhts: @js((bool) old('has_nhts')),

            stepNames: { 1: 'Personal Information', 2: 'Household', 3: 'Profile & Review' },

            init() {
                // Auto-focus/restore on return from a failed server submit
                if (this.currentStep !== 1) {
                    this.$nextTick(() => this.focusStepHeading());
                }

                if (this.isTransient && this.transientHouseholdId !== null) {
                    this.householdId = this.transientHouseholdId;
                    this.query = this.transientHouseholdLabel;
                }
                if (this.householdId && !this.isTransient && !this.creating) {
                    this.fetchHouseholdMembers();
                }

                this.$watch('firstName', () => this.checkDuplicates());
                this.$watch('lastName', () => this.checkDuplicates());
                this.$watch('dateOfBirth', () => this.checkDuplicates());
                this.checkDuplicates();
            },

            get stepName() {
                return this.stepNames[this.currentStep] || '';
            },

            get reviewName() {
                const parts = [this.lastName, this.firstName].filter(Boolean);
                if (parts.length === 0) return '—';
                return parts.join(', ') + (this.suffix ? ', ' + this.suffix : '');
            },

            get reviewSex() {
                return this.sex || '—';
            },

            get reviewAge() {
                if (!this.dateOfBirth) return '—';
                const dob = new Date(this.dateOfBirth);
                if (isNaN(dob.getTime())) return '—';
                const diff = Date.now() - dob.getTime();
                if (diff < 0) return '—';
                return Math.floor(diff / (1000 * 60 * 60 * 24 * 365.25)) + ' y/o';
            },

            get reviewHousehold() {
                if (this.creating) {
                    const head = [this.newHouseholdHeadFirst, this.newHouseholdHeadLast].filter(Boolean).join(' ');
                    const zoneSelect = document.getElementById('new_household_zone_id');
                    const zoneLabel = zoneSelect?.value ? zoneSelect.selectedOptions[0]?.text : '';
                    return 'New household' + (head ? ' - ' + head : '') + (zoneLabel ? ' · ' + zoneLabel : '');
                }
                if (this.isTransient) return this.transientHouseholdLabel;
                if (!this.householdId) return '—';
                if (!this.householdInfo) return this.query || '—';
                const member = this.householdInfo.members?.find(m => String(m.id) === String(this.householdMemberId));
                const resident = (this.householdMemberId === '' || this.householdMemberId === null)
                    ? 'new resident (to be created)'
                    : (member ? member.full_name : '—');
                return `${this.householdInfo.display_name} · Zone ${this.householdInfo.zone_number} · ${resident}`;
            },

            focusStepHeading() {
                const id = this.currentStep === 1 ? 'enroll-step-heading' : 'enroll-step-heading-' + this.currentStep;
                const heading = document.getElementById(id);
                heading?.focus();
                heading?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            },

            validateStep(step) {
                const errors = {};

                if (step === 1) {
                    if (this.firstName.trim().length < 2) errors.first_name = 'First name is required (at least 2 characters).';
                    if (this.lastName.trim().length < 2) errors.last_name = 'Last name is required (at least 2 characters).';
                    if (!this.sex) errors.sex = 'Sex is required.';
                    if (!this.dateOfBirth) {
                        errors.date_of_birth = 'Birthdate is required.';
                    } else if (this.dateOfBirth >= @js(date('Y-m-d'))) {
                        errors.date_of_birth = 'Birthdate cannot be in the future.';
                    }
                    const mother = document.getElementById('mother_name')?.value?.trim();
                    if (!mother) errors.mother_name = "Mother's name is required.";
                    const spouse = document.getElementById('spouse_name')?.value?.trim();
                    if (!spouse) errors.spouse_name = "Partner / Spouse's name is required.";
                    const relationship = document.querySelector('select[name="family_relationship"]')?.value;
                    if (!relationship) errors.family_relationship = 'Family relationship is required.';
                }

                if (step === 2) {
                    if (this.creating) {
                        if (!this.newHouseholdZone) errors.new_household_zone_id = 'Zone is required when creating a new household.';
                        if (!this.newHouseholdHeadFirst.trim()) errors.new_household_head_first_name = 'Head first name is required.';
                        if (!this.newHouseholdHeadLast.trim()) errors.new_household_head_last_name = 'Head last name (family name) is required.';
                    } else if (!this.isTransient) {
                        if (!this.householdId) {
                            errors.household_id = 'Select an existing household, mark as transient, or create a new one.';
                        } else if (this.householdInfo && this.residentChosen === false) {
                            errors.household_member_id = 'Select a resident or choose "Register as new resident".';
                        }
                    }
                }

                this.stepErrors = errors;
                if (Object.keys(errors).length > 0) {
                    this.$nextTick(() => {
                        const firstField = Object.keys(errors)[0];
                        document.getElementById(firstField)?.focus();
                        document.getElementById('step-errors-alert')?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                    });
                }
                return Object.keys(errors).length === 0;
            },

            nextStep() {
                if (!this.validateStep(this.currentStep)) return;
                this.stepErrors = {};
                this.currentStep = Math.min(3, this.currentStep + 1);
                if (this.currentStep === 2) this.prefillHouseholdSearch();
                this.focusStepHeading();
            },

            prevStep() {
                this.stepErrors = {};
                this.currentStep = Math.max(1, this.currentStep - 1);
                this.focusStepHeading();
            },

            prefillHouseholdSearch() {
                if (this.householdPrefilled) return;
                this.householdPrefilled = true;
                if (this.creating) {
                    if (!this.newHouseholdHeadLast.trim() && this.lastName.trim().length >= 2) {
                        this.newHouseholdHeadLast = this.lastName.trim();
                    }
                    return;
                }
                if (!this.query && !this.householdId && this.lastName.trim().length >= 2) {
                    this.query = this.lastName.trim();
                    this.search();
                }
            },

            // ---- Duplicate detection (non-blocking, step 1 + 3 banners) ----
            async checkDuplicates() {
                if (this.duplicateCheckTimer) clearTimeout(this.duplicateCheckTimer);
                this.duplicateCheckTimer = setTimeout(async () => {
                    const fn = this.firstName.trim();
                    const ln = this.lastName.trim();
                    const dob = this.dateOfBirth;
                    if (!fn || !ln || !dob) {
                        this.duplicateMatches = [];
                        return;
                    }
                    try {
                        const response = await safeFetch(`{{ route('search.patients') }}?query=${encodeURIComponent(fn + ' ' + ln)}`);
                        if (!response.ok) return;
                        const results = await response.json();
                        this.duplicateMatches = results.filter(p =>
                            (p.first_name || '').toLowerCase() === fn.toLowerCase() &&
                            (p.last_name || '').toLowerCase() === ln.toLowerCase() &&
                            p.date_of_birth === dob
                        );
                    } catch (e) {
                        // Non-blocking: duplicate detection never stops enrollment
                    }
                }, 500);
            },

            // ---- Household search (single consolidated input) ----
            async search() {
                if (this.isTransient) return;
                const q = (this.query || '').trim();
                if (q.length < 2) {
                    this.autocompleteResults = [];
                    this.dropdownOpen = false;
                    return;
                }

                this.autocompleteLoading = true;
                try {
                    const response = await safeFetch(`{{ route('search.households') }}?query=${encodeURIComponent(q)}`);
                    this.autocompleteResults = response.ok ? await response.json() : [];
                    this.dropdownOpen = this.autocompleteResults.length > 0;
                } catch (e) {
                    this.autocompleteResults = [];
                } finally {
                    this.autocompleteLoading = false;
                }
            },

            async select(item) {
                this.householdId = item.id;
                this.householdMemberId = '';
                this.residentChosen = false;
                this.query = item.text;
                this.autocompleteResults = [];
                this.dropdownOpen = false;
                await this.fetchHouseholdMembers();
            },

            async fetchHouseholdMembers() {
                if (!this.householdId) return;
                try {
                    const response = await safeFetch(`{{ route('households.members', ':id') }}`.replace(':id', this.householdId));
                    if (response.ok) {
                        this.householdInfo = await response.json();
                    }
                } catch (e) {
                    console.error('Failed to fetch household members:', e);
                }
            },

            clearHouseholdSelection() {
                this.householdId = null;
                this.householdMemberId = '';
                this.residentChosen = false;
                this.householdInfo = null;
                this.query = '';
            },

            enableAttaching() {
                this.creating = false;
                this.householdMemberId = '';
                this.householdInfo = null;
            },

            enableCreating() {
                this.creating = true;
                this.householdMemberId = '';
                this.householdInfo = null;
                if (!this.newHouseholdHeadLast.trim() && this.lastName.trim().length >= 2) {
                    this.newHouseholdHeadLast = this.lastName.trim();
                }
            },

            onTransientToggle() {
                this.autocompleteResults = [];
                this.dropdownOpen = false;
                this.householdMemberId = '';
                this.residentChosen = false;
                this.householdInfo = null;

                if (this.isTransient) {
                    this.previousHouseholdId = this.householdId;
                    this.previousQuery = this.query;

                    if (this.transientHouseholdId === null) {
                        this.isTransient = false;
                        return;
                    }

                    this.householdId = this.transientHouseholdId;
                    this.query = this.transientHouseholdLabel;
                    return;
                }

                if (this.previousHouseholdId !== null) {
                    this.householdId = this.previousHouseholdId;
                    this.query = this.previousQuery ?? this.query;
                    this.fetchHouseholdMembers();
                }
            },

            // ---- Birth place autocomplete ----
            async searchBirthPlace() {
                const q = (this.birthPlace || '').trim();
                if (q.length < 2) {
                    this.birthPlaceResults = [];
                    return;
                }

                this.birthPlaceSearching = true;
                try {
                    const response = await safeFetch(`{{ route('search.locations') }}?query=${encodeURIComponent(q)}`);
                    this.birthPlaceResults = response.ok ? await response.json() : [];
                } catch (e) {
                    this.birthPlaceResults = [];
                } finally {
                    this.birthPlaceSearching = false;
                }
            },

            selectBirthPlace(item) {
                this.birthPlace = item.text + ', ' + item.subtext;
                this.birthPlaceResults = [];
                this.birthPlaceOpen = false;
            },
        };
    }
</script>
@endsection
