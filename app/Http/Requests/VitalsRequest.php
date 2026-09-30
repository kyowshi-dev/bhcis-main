<?php

namespace App\Http\Requests;

use App\Services\VitalsService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class VitalsRequest extends FormRequest
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
        $rules = [
            ...VitalsService::rules(),
            'notes' => ['nullable', 'string', 'max:500'],
        ];

        // A version with every reading blank would replace the latest reading
        // with an empty one across ribbons, handouts and threshold checks.
        $rules['bp_systolic'][] = 'required_without_all:bp_diastolic,temperature,weight,height';

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'bp_systolic.required_without_all' => 'Enter at least one vitals reading.',
        ];
    }

    /**
     * Remember which vitals form failed so the view can reopen it with old input.
     */
    protected function failedValidation(Validator $validator): void
    {
        session()->flash('vitals_form', str_ends_with($this->path(), 'vitals/retake') ? 'retake' : 'edit');

        parent::failedValidation($validator);
    }
}
