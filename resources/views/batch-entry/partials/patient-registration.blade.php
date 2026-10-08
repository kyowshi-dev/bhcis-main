{{-- Patient Registration Form - Batch Entry Mode --}}
{{-- Optimized for rapid data entry from paper forms --}}
<div class="bg-surface rounded-xl lg:rounded-lg shadow-sm border border-border" x-data="patientBatchForm()" x-init="init()">

    {{-- Form Header --}}
    <div class="px-4 lg:px-6 py-3 border-b border-border" style="background: var(--teal-soft);">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-sm lg:text-base font-extrabold flex items-center" style="color: var(--ink);">
                    <span class="mr-2"><i class="fas fa-file-medical" style="color: var(--primary);" aria-hidden="true"></i></span>
                    Patient Enrollment Form
                </h2>
                <p class="text-xs" style="color: var(--ink-muted);">Enter data from the paper enrollment form. Fields with <span class="text-danger">*</span> are required.</p>
            </div>
            <div class="text-right hidden sm:block">
                <p class="text-xs" style="color: var(--ink-muted);">Entry #<span x-text="entryCount" class="font-bold" style="color: var(--primary);"></span></p>
            </div>
        </div>
    </div>

    {{-- Form Body --}}
    <form @submit.prevent="submitForm()" class="p-4 lg:p-6 space-y-4">

        {{-- Household Selection --}}
        <div class="pb-4 border-b border-border">
            <h3 class="text-sm lg:text-base font-extrabold mb-2 lg:mb-3 flex items-center" style="color: var(--ink);">
                <span class="mr-2"><i class="fas fa-home" aria-hidden="true"></i></span>
                Household
            </h3>

            <input type="hidden" name="create_new_household" :value="creatingHousehold ? 1 : 0">

            {{-- Toggle buttons --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                <button type="button"
                        @click="creatingHousehold = false"
                        :class="creatingHousehold ? 'border-border opacity-60' : 'border-primary bg-surface shadow-sm'"
                        class="rounded-xl border p-3 text-left transition-all duration-200 hover:border-primary">
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <p class="text-xs lg:text-sm font-semibold" style="color: var(--ink);">Select Existing Household</p>
                            <p class="text-xs" style="color: var(--ink-muted);">Search by family name.</p>
                        </div>
                        <span x-show="!creatingHousehold" x-cloak class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold" style="background: var(--teal-soft); color: var(--primary);">Active</span>
                    </div>
                </button>

                <button type="button"
                        @click="creatingHousehold = true"
                        :class="creatingHousehold ? 'border-primary bg-surface shadow-sm' : 'border-border opacity-60'"
                        class="rounded-xl border p-3 text-left transition-all duration-200 hover:border-primary">
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <p class="text-xs lg:text-sm font-semibold" style="color: var(--ink);">Create New Household</p>
                            <p class="text-xs" style="color: var(--ink-muted);">Add a new household and attach patient.</p>
                        </div>
                        <span x-show="creatingHousehold" x-cloak class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold" style="background: var(--teal-soft); color: var(--primary);">Active</span>
                    </div>
                </button>
            </div>

            {{-- Search existing household --}}
            <div x-show="!creatingHousehold" x-cloak>
                <div class="relative">
                    <input type="text"
                           x-ref="householdSearch"
                           x-model="householdQuery"
                           @input.debounce.250ms="searchHousehold()"
                           @focus="householdDropdownOpen = true"
                           @keydown.escape="householdDropdownOpen = false"
                           @keydown.enter.prevent="selectFirstHousehold()"
                           @click="householdDropdownOpen = true"
                           placeholder="Search household (family name / zone / contact)..."
                           class="w-full px-3 lg:px-4 py-2 lg:py-2.5 rounded-xl border border-border focus:ring-accent-blue focus:border-accent-blue text-sm"
                           autocomplete="off">

                    <div x-show="householdDropdownOpen && householdResults.length > 0"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="absolute z-20 w-full bg-surface mt-1 border border-border rounded-xl shadow-lg max-h-60 overflow-y-auto">
                        <ul>
                            <template x-for="item in householdResults" :key="item.id">
                                <li class="px-3 lg:px-4 py-2 lg:py-2.5 hover:bg-black/5 cursor-pointer border-b last:border-0 text-xs lg:text-sm"
                                    @click.prevent="selectHousehold(item)">
                                    <div class="font-medium" style="color: var(--ink);" x-text="item.text"></div>
                                    <div class="text-xs" style="color: var(--ink-muted);" x-text="item.subtext"></div>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>

                {{-- Selected household display --}}
                <div x-show="selectedHousehold" x-cloak class="mt-2 rounded-xl border px-4 py-3 flex items-center justify-between gap-3" style="border-color: var(--primary); background: var(--teal-soft);">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <i class="fa-solid fa-house-chimney text-sm" style="color: var(--primary);" aria-hidden="true"></i>
                        <div class="min-w-0">
                            <p class="text-sm font-medium truncate" style="color: var(--ink);" x-text="selectedHousehold?.text"></p>
                            <p class="text-xs" style="color: var(--ink-muted);" x-text="selectedHousehold?.subtext"></p>
                        </div>
                    </div>
                    <button type="button" @click="clearHousehold()" class="shrink-0 text-xs font-semibold hover:underline" style="color: var(--primary);">Remove</button>
                </div>

                <input type="hidden" name="household_id" :value="selectedHousehold?.id">

                <p x-show="!selectedHousehold && householdQuery.length > 0 && householdResults.length === 0 && !householdSearching" x-cloak class="text-xs mt-2" style="color: var(--ink-muted);">
                    No household found. Try a different search, or use <strong>Create New Household</strong> above.
                </p>
            </div>

            {{-- Create new household --}}
            <div x-show="creatingHousehold" x-cloak class="rounded-xl border border-border p-4" style="background: var(--teal-soft);">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 lg:gap-4">
                    <div>
                        <label class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Zone <span class="text-danger">*</span></label>
                        <select x-model="newHousehold.zone_id" required
                                class="w-full px-3 lg:px-4 py-2 rounded-xl border border-border focus:ring-accent-blue focus:border-accent-blue text-sm">
                            <option value="">Select zone</option>
                            @foreach($zones as $zone)
                                <option value="{{ $zone->id }}">Zone {{ $zone->zone_number }}</option>
                            @endforeach
                        </select>
                        <p x-show="newHouseholdErrors.zone_id" x-text="newHouseholdErrors.zone_id" class="text-danger text-xs mt-1"></p>
                    </div>
                    <div>
                        <label class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Family Name (Head) <span class="text-danger">*</span></label>
                        <input type="text" x-model="newHousehold.family_name_head" required
                               class="w-full px-3 lg:px-4 py-2 rounded-xl border border-border focus:ring-accent-blue focus:border-accent-blue text-sm"
                               placeholder="e.g. Santos">
                        <p x-show="newHouseholdErrors.family_name_head" x-text="newHouseholdErrors.family_name_head" class="text-danger text-xs mt-1"></p>
                    </div>
                    <div>
                        <label class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Contact Number <span class="text-ink-muted font-normal normal-case">(optional)</span></label>
                        <input type="text" x-model="newHousehold.contact_number"
                               class="w-full px-3 lg:px-4 py-2 rounded-xl border border-border focus:ring-accent-blue focus:border-accent-blue text-sm"
                               placeholder="09XXXXXXXXX">
                    </div>
                    <div class="flex items-end">
                        <button type="button" @click="createHousehold()"
                                :disabled="creatingHouseholdSaving || !newHousehold.zone_id || !newHousehold.family_name_head.trim()"
                                class="px-4 py-2 rounded-xl text-white font-semibold text-xs lg:text-sm shadow-md transition disabled:opacity-50"
                                style="background: var(--primary);">
                            <span x-show="!creatingHouseholdSaving">
                                <i class="fa-solid fa-plus mr-1" aria-hidden="true"></i> Create & Attach
                            </span>
                            <span x-show="creatingHouseholdSaving" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                Creating...
                            </span>
                        </button>
                    </div>
                </div>

                {{-- Show newly created household --}}
                <div x-show="selectedHousehold && creatingHousehold" x-cloak class="mt-3 rounded-xl border px-4 py-3 flex items-center justify-between gap-3" style="border-color: var(--primary); background: var(--bg-surface);">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <i class="fa-solid fa-house-chimney text-sm" style="color: var(--primary);" aria-hidden="true"></i>
                        <div class="min-w-0">
                            <p class="text-sm font-medium truncate" style="color: var(--ink);" x-text="selectedHousehold?.text"></p>
                            <p class="text-xs" style="color: var(--ink-muted);" x-text="selectedHousehold?.subtext"></p>
                        </div>
                    </div>
                    <button type="button" @click="clearHousehold()" class="shrink-0 text-xs font-semibold hover:underline" style="color: var(--primary);">Remove</button>
                </div>

                <input type="hidden" name="household_id" :value="selectedHousehold?.id">
            </div>
        </div>

        {{-- Personal Information --}}
        <div class="pb-4 border-b border-border">
            <h3 class="text-sm lg:text-base font-extrabold mb-2 lg:mb-3 flex items-center" style="color: var(--ink);">
                <span class="mr-2"><i class="fas fa-user" aria-hidden="true"></i></span>
                Personal Information
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 lg:gap-4 mb-2 lg:mb-3">
                <div>
                    <label for="batch_first_name" class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">First Name <span class="text-danger">*</span></label>
                    <input type="text" id="batch_first_name" name="first_name" x-model="form.first_name" required
                           x-ref="firstNameInput"
                           @input="checkDuplicate()"
                           class="w-full px-3 lg:px-4 py-2 rounded-xl border border-border focus:ring-accent-blue focus:border-accent-blue text-sm"
                           placeholder="e.g. Juan">
                    <p x-show="fieldErrors.first_name" x-text="fieldErrors.first_name" class="text-danger text-xs mt-1"></p>
                </div>
                <div>
                    <label for="batch_middle_name" class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Middle Name</label>
                    <input type="text" id="batch_middle_name" name="middle_name" x-model="form.middle_name"
                           class="w-full px-3 lg:px-4 py-2 rounded-xl border border-border focus:ring-accent-blue focus:border-accent-blue text-sm"
                           placeholder="e.g. Cruz">
                    <p x-show="fieldErrors.middle_name" x-text="fieldErrors.middle_name" class="text-danger text-xs mt-1"></p>
                </div>
                <div>
                    <label for="batch_last_name" class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Last Name <span class="text-danger">*</span></label>
                    <input type="text" id="batch_last_name" name="last_name" x-model="form.last_name" required
                           @input="checkDuplicate()"
                           class="w-full px-3 lg:px-4 py-2 rounded-xl border border-border focus:ring-accent-blue focus:border-accent-blue text-sm"
                           placeholder="e.g. Santos">
                    <p x-show="fieldErrors.last_name" x-text="fieldErrors.last_name" class="text-danger text-xs mt-1"></p>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 lg:gap-4 mb-2 lg:mb-3">
                <div>
                    <label for="batch_suffix" class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Suffix</label>
                    <input type="text" id="batch_suffix" name="suffix" x-model="form.suffix"
                           class="w-full px-3 lg:px-4 py-2 rounded-xl border border-border focus:ring-accent-blue focus:border-accent-blue text-sm"
                           placeholder="Jr, III">
                </div>
                <div>
                    <label for="batch_sex" class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Sex <span class="text-danger">*</span></label>
                    <select id="batch_sex" name="sex" x-model="form.sex" required
                            class="w-full px-3 lg:px-4 py-2 rounded-xl border border-border focus:ring-accent-blue focus:border-accent-blue text-sm">
                        <option value="">Select</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                    <p x-show="fieldErrors.sex" x-text="fieldErrors.sex" class="text-danger text-xs mt-1"></p>
                </div>
                <div>
                    <label for="batch_date_of_birth" class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Birthdate <span class="text-danger">*</span></label>
                    <input type="date" id="batch_date_of_birth" name="date_of_birth" x-model="form.date_of_birth" required
                           @input="checkDuplicate()"
                           class="w-full px-3 lg:px-4 py-2 rounded-xl border border-border focus:ring-accent-blue focus:border-accent-blue text-sm">
                    <p x-show="fieldErrors.date_of_birth" x-text="fieldErrors.date_of_birth" class="text-danger text-xs mt-1"></p>
                </div>
                <div>
                    <label for="batch_blood_type" class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Blood Type</label>
                    <select id="batch_blood_type" name="blood_type" x-model="form.blood_type"
                            class="w-full px-3 lg:px-4 py-2 rounded-xl border border-border focus:ring-accent-blue focus:border-accent-blue text-sm">
                        <option value="">Select</option>
                        @foreach(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 lg:gap-4 mb-2 lg:mb-3">
                <div>
                    <label for="batch_mother_name" class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Mother's Name</label>
                    <input type="text" id="batch_mother_name" name="mother_name" x-model="form.mother_name"
                           class="w-full px-3 lg:px-4 py-2 rounded-xl border border-border focus:ring-accent-blue focus:border-accent-blue text-sm"
                           placeholder="e.g. Maria Santos">
                </div>
                <div>
                    <label for="batch_spouse_name" class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Spouse/Partner Name</label>
                    <input type="text" id="batch_spouse_name" name="spouse_name" x-model="form.spouse_name"
                           class="w-full px-3 lg:px-4 py-2 rounded-xl border border-border focus:ring-accent-blue focus:border-accent-blue text-sm">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 lg:gap-4">
                <div>
                    <label for="batch_civil_status" class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Civil Status</label>
                    <select id="batch_civil_status" name="civil_status" x-model="form.civil_status"
                            class="w-full px-3 lg:px-4 py-2 rounded-xl border border-border focus:ring-accent-blue focus:border-accent-blue text-sm">
                        <option value="">Select</option>
                        @foreach(['Single (Walang Asawa)', 'Married (May Asawa)', 'Annulled (Hiwalay)', 'Widow/er (Balo)', 'Separated (Hiwalay)', 'Co-Habitation (Paninirahang magkasama)'] as $status)
                            <option value="{{ $status }}">{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="batch_family_relationship" class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Family Relationship</label>
                    <select id="batch_family_relationship" name="family_relationship" x-model="form.family_relationship"
                            class="w-full px-3 lg:px-4 py-2 rounded-xl border border-border focus:ring-accent-blue focus:border-accent-blue text-sm">
                        <option value="">Select</option>
                        @foreach(\App\Models\Patient::FAMILY_RELATIONSHIP_OPTIONS as $relationship)
                            <option value="{{ $relationship }}">{{ $relationship }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="batch_birth_place" class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Birth Place</label>
                    <div class="relative" @click.outside="birthPlaceDropdownOpen = false">
                        <input type="text" id="batch_birth_place" name="birth_place" x-model="form.birth_place"
                               @input.debounce.250ms="searchBirthPlace()"
                               @focus="birthPlaceDropdownOpen = true"
                               @keydown.escape="birthPlaceDropdownOpen = false"
                               @click="birthPlaceDropdownOpen = true"
                               :aria-expanded="birthPlaceDropdownOpen && birthPlaceResults.length > 0"
                               aria-controls="batch_birth_place_results"
                               class="w-full px-3 lg:px-4 py-2 rounded-xl border border-border focus:ring-accent-blue focus:border-accent-blue text-sm"
                               placeholder="City/Municipality, Province"
                               autocomplete="off">

                        <div id="batch_birth_place_results" role="listbox"
                             x-show="birthPlaceDropdownOpen && birthPlaceResults.length > 0"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="absolute z-20 w-full bg-surface mt-1 border border-border rounded-xl shadow-lg max-h-60 overflow-y-auto">
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
                    </div>

                    <p x-show="birthPlaceDropdownOpen && form.birth_place.trim().length > 1 && birthPlaceResults.length === 0 && !birthPlaceSearching" x-cloak class="text-xs mt-2 text-ink-muted">
                        No location found. Type the place as-is to save it.
                    </p>
                </div>
            </div>
        </div>

        {{-- Socio-Economic Status --}}
        <div class="pb-4 border-b border-border">
            <h3 class="text-sm lg:text-base font-extrabold mb-2 lg:mb-3 flex items-center" style="color: var(--ink);">
                <span class="mr-2"><i class="fas fa-peso-sign" aria-hidden="true"></i></span>
                Socio-Economic Status
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 lg:gap-4 mb-2 lg:mb-3">
                <div>
                    <label class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">PhilHealth Member? <span class="text-danger">*</span></label>
                    <div class="flex items-center gap-4 mt-1">
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="radio" id="batch_philhealth_y" name="is_philhealth_member" value="y" x-model="form.is_philhealth_member" class="h-4 w-4 text-accent-blue border-border focus:ring-accent-blue">
                            <span>Yes</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="radio" id="batch_philhealth_n" name="is_philhealth_member" value="n" x-model="form.is_philhealth_member" class="h-4 w-4 text-accent-blue border-border focus:ring-accent-blue">
                            <span>No</span>
                        </label>
                    </div>
                    <p x-show="fieldErrors.is_philhealth_member" x-text="fieldErrors.is_philhealth_member" class="text-danger text-xs mt-1"></p>
                </div>
                <div>
                    <label for="batch_philhealth_no" class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">PhilHealth Number <span class="text-ink-muted font-normal normal-case">(optional)</span></label>
                    <input type="text" id="batch_philhealth_no" name="philhealth_no" x-model="form.philhealth_no"
                           :disabled="form.is_philhealth_member !== 'y'"
                           x-bind:class="form.is_philhealth_member !== 'y' ? 'opacity-50 bg-teal-soft cursor-not-allowed' : ''"
                           class="w-full px-3 lg:px-4 py-2 rounded-xl border border-border focus:ring-accent-blue focus:border-accent-blue text-sm"
                           placeholder="12-123456789-0">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 lg:gap-4 mb-3">
                <div>
                    <label class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">PCB Member? <span class="text-danger">*</span></label>
                    <div class="flex items-center gap-4 mt-1">
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="radio" id="batch_pcb_y" name="is_pcb_member" value="y" x-model="form.is_pcb_member" class="h-4 w-4 text-accent-blue border-border focus:ring-accent-blue">
                            <span>Yes</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="radio" id="batch_pcb_n" name="is_pcb_member" value="n" x-model="form.is_pcb_member" class="h-4 w-4 text-accent-blue border-border focus:ring-accent-blue">
                            <span>No</span>
                        </label>
                    </div>
                    <p x-show="fieldErrors.is_pcb_member" x-text="fieldErrors.is_pcb_member" class="text-danger text-xs mt-1"></p>
                </div>
                <div></div>
            </div>

            <div class="flex flex-wrap gap-4 lg:gap-6 p-2 lg:p-3 rounded-xl border border-border" style="background: var(--teal-soft);">
                <div class="flex items-center">
                    <input type="checkbox" name="has_4ps" id="batch_4ps" value="1" x-model="form.has_4ps"
                           class="h-4 w-4 text-accent-blue focus:ring-accent-blue border-border rounded">
                    <label for="batch_4ps" class="ml-2 block text-xs lg:text-sm font-medium">4Ps Member</label>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="has_nhts" id="batch_nhts" value="1" x-model="form.has_nhts"
                           class="h-4 w-4 text-accent-blue focus:ring-accent-blue border-border rounded">
                    <label for="batch_nhts" class="ml-2 block text-xs lg:text-sm font-medium">NHTS / Indigent</label>
                </div>
            </div>
        </div>

        {{-- Validation Errors --}}
        <div x-show="validationErrors.length > 0" x-cloak class="rounded-xl bg-danger-soft border border-danger/30 px-3 lg:px-4 py-2 lg:py-3 text-xs lg:text-sm text-danger mb-4">
            <p class="font-semibold mb-1"><i class="fa-solid fa-exclamation-triangle mr-1" aria-hidden="true"></i> Please fix:</p>
            <ul class="list-disc list-inside">
                <template x-for="error in validationErrors" :key="error">
                    <li x-text="error"></li>
                </template>
            </ul>
        </div>

        {{-- Submit --}}
        <div class="sticky bottom-0 z-40 -mx-4 -mb-4 px-4 py-3 border-t flex flex-wrap items-center justify-between gap-3 bg-surface/95 backdrop-blur" style="border-color: var(--border);">
            <p class="text-xs" style="color: var(--ink-muted);">
                <i class="fa-solid fa-info-circle mr-1" aria-hidden="true"></i>
                Form clears after successful submission for rapid next entry.
            </p>
            <div class="flex items-center gap-3">
                <button type="button" @click="resetForm()" class="px-4 lg:px-6 py-2 rounded-xl border border-border text-xs lg:text-sm font-medium" style="color: var(--ink-muted);">
                    <i class="fa-solid fa-eraser mr-1" aria-hidden="true"></i> Clear
                </button>
                <button type="submit"
                        :disabled="submitting"
                        class="px-5 lg:px-6 py-2 lg:py-2.5 rounded-xl text-white font-semibold text-xs lg:text-sm shadow-md transition disabled:opacity-50"
                        style="background: var(--primary);">
                    <span x-show="!submitting">
                        <i class="fa-solid fa-save mr-1" aria-hidden="true"></i> Save & Next
                    </span>
                    <span x-show="submitting" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        Saving...
                    </span>
                </button>
            </div>
        </div>

    </form>
</div>

<script>
function patientBatchForm() {
    return {
        form: {
            first_name: '',
            middle_name: '',
            last_name: '',
            suffix: '',
            sex: '',
            date_of_birth: '',
            blood_type: '',
            mother_name: '',
            spouse_name: '',
            civil_status: '',
            family_relationship: '',
            birth_place: '',
            is_philhealth_member: 'n',
            philhealth_no: '',
            is_pcb_member: 'n',
            has_4ps: false,
            has_nhts: false,
        },
        householdQuery: '',
        householdResults: [],
        householdDropdownOpen: false,
        householdSearching: false,
        birthPlaceResults: [],
        birthPlaceDropdownOpen: false,
        birthPlaceSearching: false,
        selectedHousehold: null,
        creatingHousehold: false,
        newHousehold: { zone_id: '', family_name_head: '', contact_number: '' },
        newHouseholdErrors: {},
        creatingHouseholdSaving: false,
        submitting: false,
        validationErrors: [],
        fieldErrors: {},
        entryCount: 1,

        init() {
            document.addEventListener('click', (e) => {
                if (!this.$el.contains(e.target)) {
                    this.householdDropdownOpen = false;
                    this.birthPlaceDropdownOpen = false;
                }
            });
        },

        async searchHousehold() {
            const q = this.householdQuery.trim();
            if (q.length < 2) {
                this.householdResults = [];
                this.householdDropdownOpen = false;
                return;
            }

            this.householdSearching = true;
            try {
                const response = await safeFetch(`{{ route('batch-entry.patient.search-household') }}?query=${encodeURIComponent(q)}`);
                this.householdResults = response.ok ? await response.json() : [];
                this.householdDropdownOpen = this.householdResults.length > 0;
            } catch (e) {
                this.householdResults = [];
            } finally {
                this.householdSearching = false;
            }
        },

        selectHousehold(item) {
            this.selectedHousehold = item;
            this.householdQuery = item.text;
            this.householdResults = [];
            this.householdDropdownOpen = false;
        },

        selectFirstHousehold() {
            if (this.householdResults.length > 0) {
                this.selectHousehold(this.householdResults[0]);
            }
        },

        clearHousehold() {
            this.selectedHousehold = null;
            this.householdQuery = '';
            this.householdResults = [];
            this.$nextTick(() => {
                this.$refs.householdSearch?.focus();
            });
        },

        async searchBirthPlace() {
            const q = (this.form.birth_place || '').trim();
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
            this.form.birth_place = item.text + ', ' + item.subtext;
            this.birthPlaceResults = [];
            this.birthPlaceDropdownOpen = false;
        },

        async createHousehold() {
            this.newHouseholdErrors = {};
            this.creatingHouseholdSaving = true;

            try {
                const response = await fetch('{{ route("batch-entry.household.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        zone_id: this.newHousehold.zone_id,
                        family_name_head: this.newHousehold.family_name_head,
                        contact_number: this.newHousehold.contact_number,
                    }),
                });

                const data = await response.json();

                if (response.ok) {
                    this.selectedHousehold = data;
                    this.creatingHousehold = false;
                    this.newHousehold = { zone_id: '', family_name_head: '', contact_number: '' };

                    Swal.fire({
                        icon: 'success',
                        title: 'Household Created',
                        text: `"${data.text}" attached to this patient.`,
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end',
                    });
                } else {
                    const errors = data.errors || {};
                    this.newHouseholdErrors = {
                        zone_id: errors.zone_id?.[0] || '',
                        family_name_head: errors.family_name_head?.[0] || '',
                    };
                }
            } catch (e) {
                this.newHouseholdErrors = { family_name_head: 'Network error. Please try again.' };
            } finally {
                this.creatingHouseholdSaving = false;
            }
        },

        resetForm() {
            this.form = {
                first_name: '',
                middle_name: '',
                last_name: '',
                suffix: '',
                sex: '',
                date_of_birth: '',
                blood_type: '',
                mother_name: '',
                spouse_name: '',
                civil_status: '',
                family_relationship: '',
                birth_place: '',
                is_philhealth_member: 'n',
                philhealth_no: '',
                is_pcb_member: 'n',
                has_4ps: false,
                has_nhts: false,
            };
            this.validationErrors = [];
            this.fieldErrors = {};
            this.creatingHousehold = false;
            this.newHousehold = { zone_id: '', family_name_head: '', contact_number: '' };
            this.newHouseholdErrors = {};
            // Do NOT clear selectedHousehold - keep it for batch entry efficiency (household carry-over)
            this.birthPlaceResults = [];
            this.birthPlaceDropdownOpen = false;
            // Refocus first field for rapid entry
            this.$nextTick(() => {
                this.$refs.firstNameInput?.focus();
            });
        },

        // Debounced duplicate check on name + DOB
        duplicateCheckTimer: null,
        async checkDuplicate() {
            if (this.duplicateCheckTimer) {
                clearTimeout(this.duplicateCheckTimer);
            }
            this.duplicateCheckTimer = setTimeout(async () => {
                const fn = this.form.first_name?.trim();
                const ln = this.form.last_name?.trim();
                const dob = this.form.date_of_birth;
                if (fn && ln && dob) {
                    try {
                        const response = await safeFetch(`{{ route('search.patients') }}?query=${encodeURIComponent(fn + ' ' + ln)}`);
                        if (response.ok) {
                            const results = await response.json();
                            const match = results.find(p =>
                                p.first_name?.toLowerCase() === fn.toLowerCase() &&
                                p.last_name?.toLowerCase() === ln.toLowerCase() &&
                                p.date_of_birth === dob
                            );
                            if (match) {
                                this.fieldErrors = {
                                    ...this.fieldErrors,
                                    first_name: `Patient "${match.name}" with this birthdate already exists. <a href="/patients/${match.id}" target="_blank" class="underline">View profile</a>`,
                                    last_name: '',
                                    date_of_birth: '',
                                };
                            }
                        }
                    } catch (e) {
                        // Silently ignore duplicate check errors
                    }
                }
            }, 500);
        },

        async submitForm() {
            this.submitting = true;
            this.validationErrors = [];
            this.fieldErrors = {};

            if (!this.selectedHousehold) {
                this.validationErrors.push('Please select or create a household.');
                this.submitting = false;
                return;
            }

            const formData = {
                ...this.form,
                household_id: this.selectedHousehold.id,
                create_new_household: this.creatingHousehold ? 1 : 0,
            };

            try {
                const response = await fetch('{{ route("batch-entry.patient.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(formData),
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.entryCount++;

                    Swal.fire({
                        icon: 'success',
                        title: 'Patient Registered',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end',
                    });

                    this.resetForm();

                    if (data.patient) {
                        this.$dispatch('patient-added', data.patient);
                    }
                } else {
                    if (data.errors) {
                        // Map server errors to fieldErrors for inline display
                        this.fieldErrors = data.errors;
                        this.validationErrors = Object.values(data.errors).flat();
                    } else {
                        this.validationErrors = [data.message || 'An error occurred.'];
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Registration Failed',
                        text: data.message || 'Please check the form for errors.',
                    });
                }
            } catch (e) {
                this.validationErrors = ['Network error. Please check your connection and try again.'];
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Could not connect to the server. Please try again.',
                });
            } finally {
                this.submitting = false;
            }
        },
    };
}
</script>
