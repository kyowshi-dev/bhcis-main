<?php

namespace App\Http\Requests;

use App\Models\Patient;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AdministerVaccineRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('immunizations');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vaccine_id' => ['required', 'integer', 'exists:vaccines_lookup,id'],
            'dose_number' => ['nullable', 'integer', 'min:1', 'max:99'],
            'date_given' => ['nullable', 'date', 'before_or_equal:today'],
            'temp_recorded' => ['nullable', 'numeric', 'between:30,45'],
            'child_weight_kg' => ['nullable', 'numeric', 'between:0,100'],
            'child_height_cm' => ['nullable', 'numeric', 'between:20,200'],
            'override_reason' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Require weight and height for child patients (age < 18).
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $patientId = $this->route('id');
            if ($patientId === null) {
                return;
            }

            $patient = Patient::find($patientId);
            if ($patient === null || $patient->age >= 18) {
                return;
            }

            if (empty($this->input('child_weight_kg'))) {
                $validator->errors()->add('child_weight_kg', 'Weight is required for child patients.');
            }
            if (empty($this->input('child_height_cm'))) {
                $validator->errors()->add('child_height_cm', 'Height is required for child patients.');
            }
        });
    }
}
