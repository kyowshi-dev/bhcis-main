<?php

namespace App\Http\Requests;

use App\Models\HouseholdMember;
use App\Models\Patient;
use App\Rules\NameCharacters;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePatientWithHouseholdRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isCreatingHousehold = (int) $this->input('create_new_household') === 1;

        return [
            // Household creation flag
            'create_new_household' => 'nullable|boolean',

            // Household selection or creation
            'household_id' => [
                $isCreatingHousehold ? 'nullable' : 'required',
                $isCreatingHousehold ? 'nullable' : 'exists:households,id',
            ],

            // Link to existing household member (optional)
            'household_member_id' => ['nullable', 'integer', 'exists:household_members,id'],

            // New household fields (only if creating)
            'new_household_zone_id' => ['nullable', 'required_if:create_new_household,1', 'integer', 'exists:zones,id'],
            'new_household_head_first_name' => ['nullable', 'required_if:create_new_household,1', 'string', 'max:255', new NameCharacters],
            'new_household_head_last_name' => ['nullable', 'required_if:create_new_household,1', 'string', 'max:255', new NameCharacters],
            'new_household_contact_number' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+\-\s()]*$/'],

            // Patient data
            'first_name' => ['required', 'string', 'min:2', 'max:50', new NameCharacters],
            'last_name' => ['required', 'string', 'min:2', 'max:50', new NameCharacters],
            'middle_name' => ['nullable', 'string', 'max:50', new NameCharacters],

            'sex' => 'required|in:Male,Female',
            'date_of_birth' => 'required|date|before:today',
            'birth_place' => ['nullable', 'string', 'max:255', 'regex:/^[\p{L}\p{M}\d\s\-\.\',]+$/u'],

            'civil_status' => 'nullable|in:Single (Walang Asawa),Married (May Asawa),Annulled (Hiwalay),Widow/er (Balo),Separated (Hiwalay),Co-Habitation (Paninirahang magkasama)',
            'blood_type' => 'nullable|in:A+,A-,B+,B-,O+,O-,AB+,AB-',
            'educational_attainment' => 'nullable|string',
            'employment_status' => 'nullable|string|max:100',

            'mother_name' => ['required', 'string', 'max:255', new NameCharacters],
            'spouse_name' => ['required', 'string', 'max:255', new NameCharacters],
            'family_relationship' => ['required', 'in:'.implode(',', Patient::FAMILY_RELATIONSHIP_OPTIONS)],
            'is_philhealth_member' => ['required', 'in:y,n'],
            'philhealth_no' => ['nullable', 'string', 'max:20'],
            'membership_category' => ['nullable', 'in:'.implode(',', Patient::PHILHEALTH_MEMBERSHIP_CATEGORIES)],
            'status_type' => ['nullable', 'in:'.implode(',', Patient::PHILHEALTH_STATUS_TYPES)],
            'is_pcb_member' => ['required', 'in:y,n'],

            'suffix' => ['nullable', 'string', 'max:50', new NameCharacters],
            'has_4ps' => 'nullable|boolean',
            'has_nhts' => 'nullable|boolean',
        ];
    }

    /**
     * Get the validation error messages.
     */
    public function messages(): array
    {
        return [
            'first_name.name_characters' => 'First name cannot contain numbers or special symbols.',
            'last_name.name_characters' => 'Last name cannot contain numbers or special symbols.',
            'middle_name.name_characters' => 'Middle name cannot contain numbers or special symbols.',
            'date_of_birth.before' => 'Birth date cannot be in the future.',
            'household_id.required' => 'You must select an existing household or create a new one.',
            'new_household_zone_id.required_if' => 'Zone is required when creating a new household.',
            'new_household_head_first_name.required_if' => 'Head first name is required when creating a new household.',
            'new_household_head_last_name.required_if' => 'Head last name (family name) is required when creating a new household.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert checkbox values to expected payloads
        $createNew = $this->boolean('create_new_household') ? 1 : 0;
        $this->merge([
            'create_new_household' => $createNew,
            'has_4ps' => $this->boolean('has_4ps') ? 1 : 0,
            'has_nhts' => $this->boolean('has_nhts') ? 1 : 0,
            'is_philhealth_member' => in_array($this->input('is_philhealth_member'), ['y', 'yes', '1'], true) ? 'y' : 'n',
            'is_pcb_member' => in_array($this->input('is_pcb_member'), ['y', 'yes', '1'], true) ? 'y' : 'n',
        ]);

        if ($this->input('is_philhealth_member') !== 'y') {
            $this->merge([
                'philhealth_no' => null,
                'membership_category' => null,
                'status_type' => null,
            ]);
        }

        // Only set new household fields if creating new household
        if ($createNew === 1) {
            $this->merge([
                'new_household_zone_id' => $this->input('new_household_zone_id') ? (int) $this->input('new_household_zone_id') : null,
                'new_household_head_first_name' => trim((string) $this->input('new_household_head_first_name')),
                'new_household_head_last_name' => trim((string) $this->input('new_household_head_last_name')),
            ]);
        } else {
            $this->merge([
                'new_household_zone_id' => null,
                'new_household_head_first_name' => null,
                'new_household_head_last_name' => null,
                'new_household_contact_number' => null,
            ]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $memberId = $this->input('household_member_id');
            $householdId = $this->input('household_id');

            if ($memberId && $householdId) {
                $member = HouseholdMember::where('id', $memberId)
                    ->where('household_id', $householdId)
                    ->whereNull('deleted_at')
                    ->first();

                if (! $member) {
                    $validator->errors()->add('household_member_id', 'The selected household member does not belong to the chosen household.');
                } elseif ($member->patient_id !== null) {
                    $validator->errors()->add('household_member_id', 'The selected resident is already linked to another patient. Please choose a different resident or register as a new resident.');
                }
            }
        });
    }
}
