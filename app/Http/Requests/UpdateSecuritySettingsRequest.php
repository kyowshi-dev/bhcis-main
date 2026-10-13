<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSecuritySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_timeout' => ['required', 'integer', 'min:1', 'max:2880'], // 1 minute to 2 days
            'login_max_attempts' => ['required', 'integer', 'min:1', 'max:20'],
            'lockout_duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'password_min_length' => ['required', 'integer', 'min:8', 'max:64'],
            'password_require_uppercase' => ['sometimes', 'boolean'],
            'password_require_number' => ['sometimes', 'boolean'],
            'password_require_symbol' => ['sometimes', 'boolean'],
            'graceful_errors' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'session_timeout.required' => 'Session timeout is required.',
            'session_timeout.integer' => 'Session timeout must be a number.',
            'session_timeout.min' => 'Session timeout must be at least 1 minute.',
            'session_timeout.max' => 'Session timeout cannot exceed 2880 minutes (2 days).',
            'login_max_attempts.min' => 'Login attempts must be at least 1.',
            'login_max_attempts.max' => 'Login attempts cannot exceed 20.',
            'lockout_duration_minutes.min' => 'Lockout duration must be at least 1 minute.',
            'lockout_duration_minutes.max' => 'Lockout duration cannot exceed 1440 minutes (1 day).',
            'password_min_length.min' => 'Password minimum length must be at least 8 characters.',
            'password_min_length.max' => 'Password minimum length cannot exceed 64 characters.',
        ];
    }
}
