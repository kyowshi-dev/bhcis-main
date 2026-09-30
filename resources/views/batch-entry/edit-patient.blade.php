@extends('layouts.app')

@section('title', 'Edit Patient - Batch Entry')

@section('content')
<div class="max-w-4xl mx-auto space-y-3 lg:space-y-4">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-lg lg:text-xl font-bold" style="color: var(--ink);">Edit Patient Record</h1>
            <p class="text-xs lg:text-sm" style="color: var(--ink-muted);">Update patient information from batch entry.</p>
        </div>
        <a href="{{ route('batch-entry.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium border border-border transition-colors hover:bg-black/5" style="color: var(--ink-muted);">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Batch Entry
        </a>
    </div>

    {{-- Success Message --}}
    @if (session('success'))
        <div class="rounded-xl bg-primary/10 border border-primary/30 px-4 py-3 text-sm text-primary">
            <i class="fa-solid fa-check-circle mr-1" aria-hidden="true"></i> {{ session('success') }}
        </div>
    @endif

    {{-- Edit Form --}}
    <div class="bg-surface rounded-xl lg:rounded-lg shadow-sm border border-border">
        <div class="px-4 lg:px-6 py-3 border-b border-border" style="background: var(--teal-soft);">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold text-white" style="background: var(--primary);">
                    {{ strtoupper(substr($patient->first_name, 0, 1) . substr($patient->last_name, 0, 1)) }}
                </div>
                <div>
                    <h2 class="text-sm font-extrabold" style="color: var(--ink);">{{ fullName($patient->last_name, $patient->first_name, $patient->middle_name) }}</h2>
                    <p class="text-xs" style="color: var(--ink-muted);">Code: {{ $patient->patient_code }} | Household: {{ $patient->household?->family_name_head ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <form action="{{ route('batch-entry.patient.update', $patient->id) }}" method="POST" class="p-4 lg:p-6 space-y-4">
            @csrf
            @method('PUT')

            {{-- Personal Information --}}
            <div class="pb-4 border-b border-border">
                <h3 class="text-sm lg:text-base font-extrabold mb-2 lg:mb-3 flex items-center" style="color: var(--ink);">
                    <span class="mr-2"><i class="fas fa-user" aria-hidden="true"></i></span>
                    Personal Information
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 lg:gap-4 mb-2 lg:mb-3">
                    <div>
                        <label class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" value="{{ old('first_name', $patient->first_name) }}" required
                               class="w-full px-3 lg:px-4 py-2 rounded-xl border @error('first_name') border-danger bg-danger-soft @else border-border @enderror focus:ring-accent-blue focus:border-accent-blue text-sm">
                        @error('first_name') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Middle Name</label>
                        <input type="text" name="middle_name" value="{{ old('middle_name', $patient->middle_name) }}"
                               class="w-full px-3 lg:px-4 py-2 rounded-xl border @error('middle_name') border-danger bg-danger-soft @else border-border @enderror focus:ring-accent-blue focus:border-accent-blue text-sm">
                        @error('middle_name') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" value="{{ old('last_name', $patient->last_name) }}" required
                               class="w-full px-3 lg:px-4 py-2 rounded-xl border @error('last_name') border-danger bg-danger-soft @else border-border @enderror focus:ring-accent-blue focus:border-accent-blue text-sm">
                        @error('last_name') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 lg:gap-4">
                    <div>
                        <label class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Sex <span class="text-danger">*</span></label>
                        <select name="sex" required
                                class="w-full px-3 lg:px-4 py-2 rounded-xl border @error('sex') border-danger @else border-border @enderror focus:ring-accent-blue focus:border-accent-blue text-sm">
                            <option value="Male" {{ old('sex', $patient->sex) === 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('sex', $patient->sex) === 'Female' ? 'selected' : '' }}>Female</option>
                        </select>
                        @error('sex') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Birthdate <span class="text-danger">*</span></label>
                        <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $patient->date_of_birth->format('Y-m-d') ) }}" required
                               class="w-full px-3 lg:px-4 py-2 rounded-xl border @error('date_of_birth') border-danger bg-danger-soft @else border-border @enderror focus:ring-accent-blue focus:border-accent-blue text-sm">
                        @error('date_of_birth') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Civil Status</label>
                        <select name="civil_status"
                                class="w-full px-3 lg:px-4 py-2 rounded-xl border border-border focus:ring-accent-blue focus:border-accent-blue text-sm">
                            <option value="">Select</option>
                            @foreach(['Single (Walang Asawa)', 'Married (May Asawa)', 'Annulled (Hiwalay)', 'Widow/er (Balo)', 'Separated (Hiwalay)', 'Co-Habitation (Paninirahang magkasama)'] as $status)
                                <option value="{{ $status }}" {{ old('civil_status', $patient->civil_status) === $status ? 'selected' : '' }}>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Family Information --}}
            <div class="pb-4 border-b border-border">
                <h3 class="text-sm lg:text-base font-extrabold mb-2 lg:mb-3 flex items-center" style="color: var(--ink);">
                    <span class="mr-2"><i class="fas fa-users" aria-hidden="true"></i></span>
                    Family Information
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 lg:gap-4 mb-3">
                    <div>
                        <label class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Mother's Name</label>
                        <input type="text" name="mother_name" value="{{ old('mother_name', $patient->mother_name) }}"
                               class="w-full px-3 lg:px-4 py-2 rounded-xl border border-border focus:ring-accent-blue focus:border-accent-blue text-sm">
                    </div>
                    <div>
                        <label class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Spouse/Partner Name</label>
                        <input type="text" name="spouse_name" value="{{ old('spouse_name', $patient->spouse_name) }}"
                               class="w-full px-3 lg:px-4 py-2 rounded-xl border border-border focus:ring-accent-blue focus:border-accent-blue text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 lg:gap-4">
                    <div>
                        <label class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">Family Relationship</label>
                        <select name="family_relationship"
                                class="w-full px-3 lg:px-4 py-2 rounded-xl border border-border focus:ring-accent-blue focus:border-accent-blue text-sm">
                            <option value="">Select</option>
                            @foreach(\App\Models\Patient::FAMILY_RELATIONSHIP_OPTIONS as $relationship)
                                <option value="{{ $relationship }}" {{ old('family_relationship', $patient->family_relationship) === $relationship ? 'selected' : '' }}>{{ $relationship }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Socio-Economic Status --}}
            <div class="pb-4 border-b border-border">
                <h3 class="text-sm lg:text-base font-extrabold mb-2 lg:mb-3 flex items-center" style="color: var(--ink);">
                    <span class="mr-2"><i class="fas fa-peso-sign" aria-hidden="true"></i></span>
                    Socio-Economic Status
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 lg:gap-4 mb-3">
                    <div>
                        <label class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">PhilHealth Member? <span class="text-danger">*</span></label>
                        <div class="flex items-center gap-4 mt-1">
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="radio" name="is_philhealth_member" value="y" {{ old('is_philhealth_member', $patient->is_philhealth_member) === 'y' ? 'checked' : '' }} class="h-4 w-4 text-accent-blue border-border focus:ring-accent-blue">
                                <span>Yes</span>
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="radio" name="is_philhealth_member" value="n" {{ old('is_philhealth_member', $patient->is_philhealth_member) === 'n' ? 'checked' : '' }} class="h-4 w-4 text-accent-blue border-border focus:ring-accent-blue">
                                <span>No</span>
                            </label>
                        </div>
                        @error('is_philhealth_member') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs uppercase tracking-wide font-semibold text-ink-muted mb-1">PCB Member? <span class="text-danger">*</span></label>
                        <div class="flex items-center gap-4 mt-1">
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="radio" name="is_pcb_member" value="y" {{ old('is_pcb_member', $patient->is_pcb_member) === 'y' ? 'checked' : '' }} class="h-4 w-4 text-accent-blue border-border focus:ring-accent-blue">
                                <span>Yes</span>
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="radio" name="is_pcb_member" value="n" {{ old('is_pcb_member', $patient->is_pcb_member) === 'n' ? 'checked' : '' }} class="h-4 w-4 text-accent-blue border-border focus:ring-accent-blue">
                                <span>No</span>
                            </label>
                        </div>
                        @error('is_pcb_member') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Submit --}}
            <div class="sticky bottom-0 z-40 -mx-4 -mb-4 px-4 py-3 border-t flex flex-wrap items-center justify-end gap-3 bg-surface/95 backdrop-blur" style="border-color: var(--border);">
                <a href="{{ route('batch-entry.index') }}" class="px-4 lg:px-6 py-2 rounded-xl border border-border text-xs lg:text-sm font-medium" style="color: var(--ink-muted);">Cancel</a>
                <button type="submit" class="px-5 lg:px-6 py-2 lg:py-2.5 rounded-xl text-white font-semibold text-xs lg:text-sm shadow-md transition" style="background: var(--primary);">
                    <i class="fa-solid fa-save mr-1" aria-hidden="true"></i> Update Patient
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
