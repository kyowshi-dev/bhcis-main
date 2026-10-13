<?php

namespace App\Http\Requests;

use App\Rules\NameCharacters;
use Illuminate\Foundation\Http\FormRequest;

class StoreHouseholdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'head_first_name' => ['required', 'string', 'max:255', new NameCharacters],
            'head_middle_name' => ['nullable', 'string', 'max:255', new NameCharacters],
            'head_last_name' => ['required', 'string', 'max:255', new NameCharacters],
            'head_suffix' => ['nullable', 'string', 'max:255', new NameCharacters],
            'contact_number' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+\-\s()]*$/'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'head_first_name' => trim((string) $this->input('head_first_name')),
            'head_middle_name' => $this->input('head_middle_name') !== null ? trim((string) $this->input('head_middle_name')) : null,
            'head_last_name' => trim((string) $this->input('head_last_name')),
            'head_suffix' => $this->input('head_suffix') !== null ? trim((string) $this->input('head_suffix')) : null,
        ]);
    }
}
