<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePrivacyPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'privacy_policy_content' => ['nullable', 'string'],
            'privacy_policy_version' => ['nullable', 'string', 'max:50'],
            'purpose_limitation' => ['nullable', 'array'],
            'purpose_limitation.*' => ['string', 'max:500'],
            'data_retention_days' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
