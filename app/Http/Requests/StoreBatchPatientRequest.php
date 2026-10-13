<?php

namespace App\Http\Requests;

use App\Models\Patient;
use App\Rules\NameCharacters;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBatchPatientRequest extends FormRequest
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

            // New household fields (only if creating)
            'new_household_zone_id' => ['nullable', 'required_if:create_new_household,1', 'integer', 'exists:zones,id'],
            'new_household_head_first_name' => ['nullable', 'required_if:create_new_household,1', 'string', 'max:255', new NameCharacters],
            'new_household_head_last_name' => ['nullable', 'required_if:create_new_household,1', 'string', 'max:255', new NameCharacters],
            'new_household_contact_number' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+\-\s()]*$/'],

            // Patient data - required fields for DOH compliance
            'first_name' => ['required', 'string', 'min:2', 'max:50', new NameCharacters],
            'last_name' => ['required', 'string', 'min:2', 'max:50', new NameCharacters],
            'middle_name' => ['nullable', 'string', 'max:50', new NameCharacters],
            'suffix' => ['nullable', 'string', 'max:50'],

            'sex' => 'required|in:Male,Female',
            'date_of_birth' => 'required|date|before:today',
            'birth_place' => ['nullable', 'string', 'max:255'],
            'blood_type' => 'nullable|in:A+,A-,B+,B-,O+,O-,AB+,AB-',

            'civil_status' => 'nullable|string',
            'educational_attainment' => 'nullable|string',
            'employment_status' => 'nullable|string|max:100',

            'mother_name' => ['nullable', 'string', 'max:255'],
            'spouse_name' => ['nullable', 'string', 'max:255'],
            'family_relationship' => ['nullable', 'string'],

            'is_philhealth_member' => ['required', 'in:y,n'],
            'philhealth_no' => ['nullable', 'string', 'max:20'],
            'membership_category' => ['nullable', 'string'],
            'status_type' => ['nullable', 'string'],

            'is_pcb_member' => ['required', 'in:y,n'],
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
            'first_name.required' => 'First name is required.',
            'first_name.name_characters' => 'First name cannot contain numbers or special symbols.',
            'last_name.required' => 'Last name is required.',
            'last_name.name_characters' => 'Last name cannot contain numbers or special symbols.',
            'date_of_birth.required' => 'Birth date is required.',
            'date_of_birth.before' => 'Birth date cannot be in the future.',
            'household_id.required' => 'You must select an existing household or create a new one.',
            'household_id.exists' => 'Selected household does not exist.',
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
}
