<?php

namespace App\Http\Requests;

use App\Models\Patient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $civilStatusOptions = [
            'Single (Walang Asawa)',
            'Married (May Asawa)',
            'Annulled (Hiwalay)',
            'Widow/er (Balo)',
            'Separated (Hiwalay)',
            'Co-Habitation (Paninirahang magkasama)',
        ];

        // Legacy rows predate the canonical option labels (e.g. "Married").
        // Accept whatever this patient already has so unrelated edits never fail;
        // new choices must come from the canonical list.
        $currentCivilStatus = Patient::query()
            ->whereKey($this->route('id'))
            ->value('civil_status');

        if (is_string($currentCivilStatus) && $currentCivilStatus !== '') {
            $civilStatusOptions[] = $currentCivilStatus;
        }

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'sex' => 'required|in:Male,Female',
            'date_of_birth' => 'required|date|before:today',
            'blood_type' => 'nullable|in:A+,A-,B+,B-,O+,O-,AB+,AB-',
            // NOT NULL column - an empty select must never reach the database as null.
            'civil_status' => ['required', Rule::in($civilStatusOptions)],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'spouse_name' => ['nullable', 'string', 'max:255'],
            // Matches the patients.family_relationship ENUM column.
            'family_relationship' => ['nullable', 'in:'.implode(',', Patient::FAMILY_RELATIONSHIP_OPTIONS)],
            'residential_address' => ['nullable', 'string', 'max:255'],
            'philhealth_no' => ['nullable', 'string', 'max:50'],
            'is_philhealth_member' => ['boolean'],
            'is_pcb_member' => ['boolean'],
            'has_4ps' => ['boolean'],
            'has_nhts' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'sex.required' => 'Sex is required.',
            'sex.in' => 'Sex must be Male or Female.',
            'date_of_birth.required' => 'Birth date is required.',
            'date_of_birth.before' => 'Birth date cannot be in the future.',
            'civil_status.required' => 'Civil status is required.',
            'civil_status.in' => 'Please select a valid civil status.',
            'family_relationship.in' => 'Please select a valid relationship.',
        ];
    }
}
