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
use Illuminate\Support\Facades\DB;
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

        $households = DB::table('households')
            ->join('zones', 'households.zone_id', '=', 'zones.id')
            ->where(function ($qb) use ($query) {
                $qb->where('households.head_first_name', 'LIKE', "%{$query}%")
                    ->orWhere('households.head_last_name', 'LIKE', "%{$query}%")
                    ->orWhere('zones.zone_number', 'LIKE', "%{$query}%")
                    ->orWhere('households.contact_number', 'LIKE', "%{$query}%");
            })
            ->distinct()
            ->select('households.id', 'households.head_first_name', 'households.head_middle_name', 'households.head_last_name', 'households.head_suffix', 'zones.zone_number', 'households.contact_number')
            ->limit(10)
            ->get()
            ->map(function ($h) {
                $contact = $h->contact_number ? " - {$h->contact_number}" : '';
                $headName = fullName($h->head_last_name, $h->head_first_name, $h->head_middle_name, $h->head_suffix);

                return [
                    'id' => $h->id,
                    'text' => $headName,
                    'subtext' => "Head: {$headName} | Zone {$h->zone_number}{$contact}",
                ];
            });

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
            'head_first_name' => ucwords(strtolower($validated['head_first_name'])),
            'head_last_name' => ucwords(strtolower($validated['head_last_name'])),
            'contact_number' => $validated['contact_number'] ?? null,
        ]);

        $household->load('zone:id,zone_number');

        return response()->json([
            'id' => $household->id,
            'text' => $household->display_name,
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
