<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeathRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_of_death' => ['sometimes', 'date', 'before_or_equal:today'],
            'place_of_death' => ['sometimes', 'string', 'max:255'],
            'cause_diagnosis_id' => ['nullable', 'integer', 'exists:diagnosis_lookup,id'],
            'cause_text' => ['nullable', 'string', 'max:1000'],
            'maternal_death' => ['boolean'],
            'perinatal_death' => ['boolean'],
            'neonatal_death' => ['boolean'],
            'infant_death' => ['boolean'],
            'under_five_death' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_of_death.date' => 'Date of death must be a valid date.',
            'date_of_death.before_or_equal' => 'Date of death cannot be in the future.',
            'place_of_death.max' => 'Place of death must not exceed 255 characters.',
            'cause_diagnosis_id.exists' => 'Selected diagnosis does not exist.',
            'cause_text.max' => 'Cause of death text must not exceed 1000 characters.',
        ];
    }
}
