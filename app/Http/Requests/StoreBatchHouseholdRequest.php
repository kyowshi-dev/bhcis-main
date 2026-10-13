<?php

namespace App\Http\Requests;

use App\Rules\NameCharacters;
use Illuminate\Foundation\Http\FormRequest;

class StoreBatchHouseholdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'head_first_name' => ['required', 'string', 'max:255', new NameCharacters],
            'head_last_name' => ['required', 'string', 'max:255', new NameCharacters],
            'contact_number' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+\-\s()]*$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'zone_id.required' => 'Zone is required.',
            'zone_id.exists' => 'Selected zone does not exist.',
            'head_first_name.required' => 'Head first name is required.',
            'head_first_name.name_characters' => 'Head name cannot contain numbers or special symbols.',
            'head_last_name.required' => 'Head last name (family name) is required.',
            'head_last_name.name_characters' => 'Head name cannot contain numbers or special symbols.',
            'contact_number.regex' => 'Contact number contains invalid characters.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'zone_id' => $this->input('zone_id') ? (int) $this->input('zone_id') : null,
            'head_first_name' => trim($this->input('head_first_name') ?? ''),
            'head_last_name' => trim($this->input('head_last_name') ?? ''),
            'contact_number' => trim($this->input('contact_number') ?? ''),
        ]);
    }
}
