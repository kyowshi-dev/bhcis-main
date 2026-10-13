<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApplicationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'projected_population' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'session_timeout' => ['required', 'integer', 'min:5', 'max:2880'],
        ];
    }

    public function messages(): array
    {
        return [
            'projected_population.integer' => 'Projected population must be a number.',
            'projected_population.min' => 'Projected population must be at least 0.',
            'projected_population.max' => 'Projected population must not exceed 999,999,999.',
            'session_timeout.required' => 'Session timeout is required.',
            'session_timeout.integer' => 'Session timeout must be a number.',
            'session_timeout.min' => 'Session timeout must be at least 5 minutes.',
            'session_timeout.max' => 'Session timeout must not exceed 48 hours.',
        ];
    }
}
