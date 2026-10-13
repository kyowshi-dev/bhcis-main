<div class="space-y-4">
    <!-- Name Fields -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
            <label for="{{ $isEdit ? 'edit_first_name' : 'first_name' }}" class="block text-xs font-semibold mb-1" style="color: var(--ink-muted);">
                First Name <span style="color: var(--danger);">*</span>
            </label>
            <input type="text" id="{{ $isEdit ? 'edit_first_name' : 'first_name' }}" name="first_name"
                   value="{{ $member?->first_name ?? old('first_name') }}"
                   required maxlength="255"
                   class="w-full px-3 py-2 rounded-lg border text-sm @error('first_name') border-danger bg-danger-soft @else border-border @enderror focus:ring-accent-blue focus:border-accent-blue"
                   style="border-color: var(--border); background: var(--bg-surface);" autocomplete="off">
            @error('first_name')
                <p class="text-xs mt-1" style="color: var(--danger);" data-auto-dismiss>{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="{{ $isEdit ? 'edit_middle_name' : 'middle_name' }}" class="block text-xs font-semibold mb-1" style="color: var(--ink-muted);">
                Middle Name
            </label>
            <input type="text" id="{{ $isEdit ? 'edit_middle_name' : 'middle_name' }}" name="middle_name"
                   value="{{ $member?->middle_name ?? old('middle_name') }}"
                   maxlength="255"
                   class="w-full px-3 py-2 rounded-lg border text-sm @error('middle_name') border-danger bg-danger-soft @else border-border @enderror focus:ring-accent-blue focus:border-accent-blue"
                   style="border-color: var(--border); background: var(--bg-surface);" autocomplete="off">
            @error('middle_name')
                <p class="text-xs mt-1" style="color: var(--danger);" data-auto-dismiss>{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="{{ $isEdit ? 'edit_last_name' : 'last_name' }}" class="block text-xs font-semibold mb-1" style="color: var(--ink-muted);">
                Last Name <span style="color: var(--danger);">*</span>
            </label>
            <input type="text" id="{{ $isEdit ? 'edit_last_name' : 'last_name' }}" name="last_name"
                   value="{{ $member?->last_name ?? old('last_name') }}"
                   required maxlength="255"
                   class="w-full px-3 py-2 rounded-lg border text-sm @error('last_name') border-danger bg-danger-soft @else border-border @enderror focus:ring-accent-blue focus:border-accent-blue"
                   style="border-color: var(--border); background: var(--bg-surface);" autocomplete="off">
            @error('last_name')
                <p class="text-xs mt-1" style="color: var(--danger);" data-auto-dismiss>{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="{{ $isEdit ? 'edit_suffix' : 'suffix' }}" class="block text-xs font-semibold mb-1" style="color: var(--ink-muted);">
                Suffix
            </label>
            <input type="text" id="{{ $isEdit ? 'edit_suffix' : 'suffix' }}" name="suffix"
                   value="{{ $member?->suffix ?? old('suffix') }}"
                   maxlength="50"
                   class="w-full px-3 py-2 rounded-lg border text-sm @error('suffix') border-danger bg-danger-soft @else border-border @enderror focus:ring-accent-blue focus:border-accent-blue"
                   style="border-color: var(--border); background: var(--bg-surface);" autocomplete="off">
            @error('suffix')
                <p class="text-xs mt-1" style="color: var(--danger);" data-auto-dismiss>{{ $message }}</p>
            @enderror
        </div>
    </div>

    <!-- Sex -->
    <div>
        <label class="block text-xs font-semibold mb-1" style="color: var(--ink-muted);">
            Sex <span style="color: var(--danger);">*</span>
        </label>
        <div class="flex gap-4">
            <label class="inline-flex items-center cursor-pointer">
                <input type="radio" name="sex" value="Male"
                       {{ (!$isEdit || ($member?->sex === 'Male') || old('sex') === 'Male') ? 'checked' : '' }}
                       class="h-4 w-4 text-accent-blue focus:ring-accent-blue border-border rounded">
                <span class="ml-2 text-sm" style="color: var(--ink);">Male</span>
            </label>
            <label class="inline-flex items-center cursor-pointer">
                <input type="radio" name="sex" value="Female"
                       {{ ($member?->sex === 'Female') || old('sex') === 'Female' ? 'checked' : '' }}
                       class="h-4 w-4 text-accent-blue focus:ring-accent-blue border-border rounded">
                <span class="ml-2 text-sm" style="color: var(--ink);">Female</span>
            </label>
        </div>
        @error('sex')
            <p class="text-xs mt-1" style="color: var(--danger);" data-auto-dismiss>{{ $message }}</p>
        @enderror
    </div>

    <!-- DOB or Age -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
            <label for="{{ $isEdit ? 'edit_date_of_birth' : 'date_of_birth' }}" class="block text-xs font-semibold mb-1" style="color: var(--ink-muted);">
                Date of Birth
            </label>
            <input type="date" id="{{ $isEdit ? 'edit_date_of_birth' : 'date_of_birth' }}" name="date_of_birth"
                   value="{{ $member?->date_of_birth ? $member->date_of_birth->format('Y-m-d') : old('date_of_birth') }}"
                   class="w-full px-3 py-2 rounded-lg border text-sm @error('date_of_birth') border-danger bg-danger-soft @else border-border @enderror focus:ring-accent-blue focus:border-accent-blue"
                   style="border-color: var(--border); background: var(--bg-surface);">
            @error('date_of_birth')
                <p class="text-xs mt-1" style="color: var(--danger);" data-auto-dismiss>{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="{{ $isEdit ? 'edit_age' : 'age' }}" class="block text-xs font-semibold mb-1" style="color: var(--ink-muted);">
                Age (years) <span class="text-xs text-ink-muted">(if DOB unknown)</span>
            </label>
            <input type="number" id="{{ $isEdit ? 'edit_age' : 'age' }}" name="age"
                   value="{{ $member?->age ?? old('age') }}"
                   min="0" max="120"
                   class="w-full px-3 py-2 rounded-lg border text-sm @error('age') border-danger bg-danger-soft @else border-border @enderror focus:ring-accent-blue focus:border-accent-blue"
                   style="border-color: var(--border); background: var(--bg-surface);">
            @error('age')
                <p class="text-xs mt-1" style="color: var(--danger);" data-auto-dismiss>{{ $message }}</p>
            @enderror
        </div>
    </div>

    <p class="text-xs text-ink-muted">Either Date of Birth or Age is required.</p>

    <!-- Relationship -->
    <div>
        <label for="{{ $isEdit ? 'edit_relationship' : 'relationship' }}" class="block text-xs font-semibold mb-1" style="color: var(--ink-muted);">
            Relationship to Head <span style="color: var(--danger);">*</span>
        </label>
        <select id="{{ $isEdit ? 'edit_relationship' : 'relationship' }}" name="relationship"
                class="w-full px-3 py-2 rounded-lg border text-sm @error('relationship') border-danger bg-danger-soft @else border-border @enderror focus:ring-accent-blue focus:border-accent-blue"
                style="border-color: var(--border); background: var(--bg-surface);">
            <option value="">Select relationship</option>
            <option value="Spouse" {{ ($member?->relationship === 'Spouse') || old('relationship') === 'Spouse' ? 'selected' : '' }}>Spouse</option>
            <option value="Son" {{ ($member?->relationship === 'Son') || old('relationship') === 'Son' ? 'selected' : '' }}>Son</option>
            <option value="Daughter" {{ ($member?->relationship === 'Daughter') || old('relationship') === 'Daughter' ? 'selected' : '' }}>Daughter</option>
            <option value="Father" {{ ($member?->relationship === 'Father') || old('relationship') === 'Father' ? 'selected' : '' }}>Father</option>
            <option value="Mother" {{ ($member?->relationship === 'Mother') || old('relationship') === 'Mother' ? 'selected' : '' }}>Mother</option>
            <option value="Sibling" {{ ($member?->relationship === 'Sibling') || old('relationship') === 'Sibling' ? 'selected' : '' }}>Sibling</option>
            <option value="Grandchild" {{ ($member?->relationship === 'Grandchild') || old('relationship') === 'Grandchild' ? 'selected' : '' }}>Grandchild</option>
            <option value="Grandparent" {{ ($member?->relationship === 'Grandparent') || old('relationship') === 'Grandparent' ? 'selected' : '' }}>Grandparent</option>
            <option value="In-law" {{ ($member?->relationship === 'In-law') || old('relationship') === 'In-law' ? 'selected' : '' }}>In-law</option>
            <option value="Other" {{ ($member?->relationship === 'Other') || old('relationship') === 'Other' ? 'selected' : '' }}>Other</option>
        </select>
        @error('relationship')
            <p class="text-xs mt-1" style="color: var(--danger);" data-auto-dismiss>{{ $message }}</p>
        @enderror
    </div>
</div>