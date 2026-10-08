<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBatchHouseholdRequest;
use App\Http\Requests\StoreBatchPatientRequest;
use App\Http\Requests\UpdateBatchPatientRequest;
use App\Models\Household;
use App\Models\Patient;
use App\Models\Zone;
use App\Services\BatchEntryService;
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

        $zones = Zone::orderBy('zone_number')->get();

        $recentPatients = Patient::with('household')
            ->latest()
            ->limit(10)
            ->get();

        return view('batch-entry.index', [
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
    public function createHousehold(StoreBatchHouseholdRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $household = Household::create([
            'zone_id' => $validated['zone_id'],
            'family_name_head' => $validated['family_name_head'],
            'contact_number' => $validated['contact_number'] ?? null,
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

        return view('batch-entry.edit-patient', [
            'patient' => $patient,
            'zones' => $zones,
        ]);
    }

    /**
     * Update a patient from batch entry edit form.
     */
    public function updatePatient(UpdateBatchPatientRequest $request, int $id): RedirectResponse
    {
        $patient = Patient::findOrFail($id);
        $this->authorize('update', $patient);

        $validated = $request->validated();

        $patient->update($validated);

        return redirect()
            ->route('batch-entry.index')
            ->with('success', "Patient updated: {$patient->first_name} {$patient->last_name}");
    }
}
