<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHouseholdMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'sex' => ['required', 'in:Male,Female'],
            'date_of_birth' => ['nullable', 'date', 'required_without:age'],
            'age' => ['nullable', 'integer', 'min:0', 'max:120', 'required_without:date_of_birth'],
            'relationship' => ['required', 'string', Rule::in([
                'Spouse', 'Son', 'Daughter', 'Father', 'Mother',
                'Sibling', 'Grandchild', 'Grandparent', 'In-law', 'Other',
            ])],
        ];
    }

    public function messages(): array
    {
        return [
            'date_of_birth.required_without' => 'Either date of birth or age is required.',
            'age.required_without' => 'Either date of birth or age is required.',
        ];
    }
}
