<?php

namespace App\Http\Requests;

use App\Rules\NameCharacters;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBatchPatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'min:2', 'max:50', new NameCharacters],
            'last_name' => ['required', 'string', 'min:2', 'max:50', new NameCharacters],
            'middle_name' => ['nullable', 'string', 'max:50', new NameCharacters],
            'suffix' => ['nullable', 'string', 'max:50'],
            'sex' => 'required|in:Male,Female',
            'date_of_birth' => 'required|date|before:today',
            'civil_status' => 'nullable|string',
            'mother_name' => ['nullable', 'string', 'max:255'],
            'spouse_name' => ['nullable', 'string', 'max:255'],
            'family_relationship' => ['nullable', 'string'],
            'is_philhealth_member' => ['required', 'in:y,n'],
            'philhealth_no' => ['nullable', 'string', 'max:20'],
            'is_pcb_member' => ['required', 'in:y,n'],
            'has_4ps' => 'nullable|boolean',
            'has_nhts' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'first_name.name_characters' => 'First name cannot contain numbers or special symbols.',
            'last_name.required' => 'Last name is required.',
            'last_name.name_characters' => 'Last name cannot contain numbers or special symbols.',
            'date_of_birth.required' => 'Birth date is required.',
            'date_of_birth.before' => 'Birth date cannot be in the future.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name' => $this->input('first_name') ? ucwords(strtolower($this->input('first_name'))) : null,
            'last_name' => $this->input('last_name') ? ucwords(strtolower($this->input('last_name'))) : null,
            'middle_name' => $this->input('middle_name') ? ucfirst(strtolower($this->input('middle_name'))) : null,
            'has_4ps' => $this->boolean('has_4ps') ? 1 : 0,
            'has_nhts' => $this->boolean('has_nhts') ? 1 : 0,
            'is_philhealth_member' => in_array($this->input('is_philhealth_member'), ['y', 'yes', '1'], true) ? 'y' : 'n',
            'is_pcb_member' => in_array($this->input('is_pcb_member'), ['y', 'yes', '1'], true) ? 'y' : 'n',
        ]);

        if ($this->input('is_philhealth_member') !== 'y') {
            $this->merge(['philhealth_no' => null]);
        }
    }
}
