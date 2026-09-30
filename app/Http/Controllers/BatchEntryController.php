<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBatchPatientRequest;
use App\Models\Household;
use App\Models\Patient;
use App\Models\Zone;
use App\Services\BatchEntryService;
use App\Services\PatientService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BatchEntryController extends Controller
{
    /**
     * Show the batch entry dashboard with tabbed interface.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Patient::class);

        $transientHousehold = PatientService::ensureTransientHousehold();
        $zones = Zone::orderBy('zone_number')->get();

        $recentPatients = Patient::with('household')
            ->latest()
            ->limit(10)
            ->get();

        return view('batch-entry.index', [
            'transientHouseholdId' => $transientHousehold->id,
            'transientHouseholdLabel' => $transientHousehold->family_name_head,
            'zones' => $zones,
            'recentPatients' => $recentPatients,
        ]);
    }

    /**
     * Store a new patient from batch entry form.
     * Returns JSON for AJAX submission, or redirect for form submission.
     */
    public function storePatient(StoreBatchPatientRequest $request): JsonResponse|RedirectResponse
    {
        $this->authorize('create', Patient::class);

        try {
            $patient = BatchEntryService::registerPatient($request->validated());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Patient registered: {$patient->first_name} {$patient->last_name}",
                    'patient' => [
                        'id' => $patient->id,
                        'name' => fullName($patient->last_name, $patient->first_name, $patient->middle_name),
                        'patient_code' => $patient->patient_code,
                    ],
                ]);
            }

            return redirect()
                ->route('batch-entry.index')
                ->with('success', "Patient registered: {$patient->first_name} {$patient->last_name}")
                ->with('new_patient_id', $patient->id);

        } catch (ValidationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }

            return back()->withInput()->withErrors($e->errors());
        }
    }

    /**
     * Search households for the batch entry form (AJAX autocomplete).
     */
    public function searchHousehold(Request $request): JsonResponse
    {
        $query = $request->input('query', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $households = Household::query()
            ->where('family_name_head', 'LIKE', "%{$query}%")
            ->select(['id', 'family_name_head', 'zone_id', 'contact_number'])
            ->with('zone:id,zone_number')
            ->limit(10)
            ->get()
            ->map(fn ($h) => [
                'id' => $h->id,
                'text' => $h->family_name_head,
                'subtext' => ($h->zone ? "Zone {$h->zone->zone_number}" : 'No zone').($h->contact_number ? " - {$h->contact_number}" : ''),
            ]);

        return response()->json($households);
    }

    /**
     * Create a new household inline from the batch entry form (AJAX).
     */
    public function createHousehold(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'family_name_head' => ['required', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+\-\s()]*$/'],
        ]);

        $household = Household::create([
            'zone_id' => $validated['zone_id'],
            'family_name_head' => trim($validated['family_name_head']),
            'contact_number' => ($validated['contact_number'] ?? null) !== null ? trim($validated['contact_number']) : null,
        ]);

        $household->load('zone:id,zone_number');

        return response()->json([
            'id' => $household->id,
            'text' => $household->family_name_head,
            'subtext' => ($household->zone ? "Zone {$household->zone->zone_number}" : 'No zone').($household->contact_number ? " - {$household->contact_number}" : ''),
        ]);
    }

    /**
     * Show edit form for a patient (inline edit from batch entry).
     */
    public function editPatient(int $id): View
    {
        $patient = Patient::findOrFail($id);
        $this->authorize('update', $patient);

        $zones = Zone::orderBy('zone_number')->get();
        $transientHousehold = PatientService::ensureTransientHousehold();

        return view('batch-entry.edit-patient', [
            'patient' => $patient,
            'zones' => $zones,
            'transientHouseholdId' => $transientHousehold->id,
            'transientHouseholdLabel' => $transientHousehold->family_name_head,
        ]);
    }

    /**
     * Update a patient from batch entry edit form.
     */
    public function updatePatient(Request $request, int $id): RedirectResponse
    {
        $patient = Patient::findOrFail($id);
        $this->authorize('update', $patient);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'min:2', 'max:50'],
            'last_name' => ['required', 'string', 'min:2', 'max:50'],
            'middle_name' => ['nullable', 'string', 'max:50'],
            'sex' => 'required|in:Male,Female',
            'date_of_birth' => 'required|date|before:today',
            'civil_status' => 'nullable|string',
            'mother_name' => ['nullable', 'string', 'max:255'],
            'spouse_name' => ['nullable', 'string', 'max:255'],
            'family_relationship' => ['nullable', 'string'],
            'is_philhealth_member' => ['required', 'in:y,n'],
            'philhealth_no' => ['nullable', 'string', 'max:20'],
            'is_pcb_member' => ['required', 'in:y,n'],
        ]);

        $patient->update([
            'first_name' => ucwords(strtolower($validated['first_name'])),
            'last_name' => ucwords(strtolower($validated['last_name'])),
            'middle_name' => $validated['middle_name'] ? ucfirst(strtolower($validated['middle_name'])) : null,
            'sex' => $validated['sex'],
            'date_of_birth' => $validated['date_of_birth'],
            'civil_status' => $validated['civil_status'] ?? $patient->civil_status,
            'mother_name' => $validated['mother_name'] ?? $patient->mother_name,
            'spouse_name' => $validated['spouse_name'] ?? $patient->spouse_name,
            'family_relationship' => $validated['family_relationship'] ?? $patient->family_relationship,
            'is_philhealth_member' => $validated['is_philhealth_member'],
            'philhealth_no' => $validated['philhealth_no'] ?? $patient->philhealth_no,
            'is_pcb_member' => $validated['is_pcb_member'],
        ]);

        return redirect()
            ->route('batch-entry.index')
            ->with('success', "Patient updated: {$patient->first_name} {$patient->last_name}");
    }
}
