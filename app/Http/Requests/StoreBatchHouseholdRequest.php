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
            'family_name_head' => ['required', 'string', 'max:255', new NameCharacters],
            'contact_number' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+\-\s()]*$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'zone_id.required' => 'Zone is required.',
            'zone_id.exists' => 'Selected zone does not exist.',
            'family_name_head.required' => 'Family name (head) is required.',
            'family_name_head.name_characters' => 'Family name cannot contain numbers or special symbols.',
            'contact_number.regex' => 'Contact number contains invalid characters.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'zone_id' => $this->input('zone_id') ? (int) $this->input('zone_id') : null,
            'family_name_head' => trim($this->input('family_name_head') ?? ''),
            'contact_number' => trim($this->input('contact_number') ?? ''),
        ]);
    }
}
