<?php

namespace App\Http\Requests;

use App\Enums\MedicationRoute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        return [
            'medicine_name' => ['required', 'string', 'max:255'],
            'medicine_id' => ['nullable', 'integer', Rule::exists('medicines_lookup', 'id')->whereNull('deleted_at')],
            'dosage' => ['required', 'string', 'max:255'],
            'route' => ['required', 'string', Rule::in(MedicationRoute::values())],
            'frequency' => ['required', 'string', 'max:255'],
            'duration' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'instructions' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Reject saving a medicine that already appears on this consultation,
     * whether the existing row is directory-linked or free text.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $consultation = $this->route('consultation');
            $medicineName = trim((string) $this->input('medicine_name'));
            $medicineId = $this->input('medicine_id');

            if ($consultation && $medicineName !== '') {
                $existingId = (int) $this->route('prescriptionId');

                $exists = DB::table('prescriptions')
                    ->leftJoin('medicines_lookup', 'medicines_lookup.id', '=', 'prescriptions.medicine_id')
                    ->where('prescriptions.consultation_id', $consultation->id)
                    ->where('prescriptions.id', '!=', $existingId)
                    ->where(function ($query) use ($medicineName, $medicineId) {
                        $query->whereRaw(
                            'LOWER(COALESCE(prescriptions.custom_medicine_name, medicines_lookup.name)) = ?',
                            [strtolower($medicineName)]
                        );

                        if ($medicineId !== null) {
                            $query->orWhere('prescriptions.medicine_id', (int) $medicineId);
                        }
                    })
                    ->exists();

                if ($exists) {
                    $validator->errors()->add(
                        'medicine_name',
                        'This medicine is already on the current prescription. Edit or delete the existing entry instead of duplicating it.'
                    );
                }
            }
        });
    }
}
