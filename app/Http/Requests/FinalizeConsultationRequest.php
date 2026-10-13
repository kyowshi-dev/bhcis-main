<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

class FinalizeConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'refer_to_higher_facility' => ['nullable', 'boolean'],
            'referred_to' => ['required_if:refer_to_higher_facility,1', 'nullable', 'string', 'max:255'],
            'referral_reasons' => ['nullable', 'array'],
            'referral_reasons.*' => ['string'],
            'referral_reason_details' => ['nullable', 'string', 'max:1000'],
            'pertinent_history' => ['required_if:refer_to_higher_facility,1', 'nullable', 'string'],
            'actions_taken' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $consultation = $this->route('consultation');
            $hasDiagnosis = DB::table('diagnosis_records')->where('consultation_id', $consultation->id)->exists();
            $hasPrescription = DB::table('prescriptions')->where('consultation_id', $consultation->id)->exists();

            if (! $hasDiagnosis) {
                $validator->errors()->add('clinical', $hasPrescription
                    ? 'A prescription requires a diagnosis. Add a diagnosis before finalizing the consultation.'
                    : 'Add at least one diagnosis before finalizing the consultation.');
            }
        });
    }
}
