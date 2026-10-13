<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeathRequest;
use App\Http\Requests\UpdateDeathRequest;
use App\Models\Death;
use App\Models\Patient;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DeathController extends Controller
{
    public function __construct(
        private readonly AuditLogService $audit,
    ) {}

    /**
     * Show the form for creating a new death record for a patient.
     */
    public function create(Patient $patient): View
    {
        return view('deaths.create', [
            'patient' => $patient,
        ]);
    }

    /**
     * Store a newly created death record.
     */
    public function store(Patient $patient, StoreDeathRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['patient_id'] = $patient->id;

        $death = Death::create($validated);

        $this->audit->log('death_created', 'deaths', $request, null, $death->id);

        return redirect()
            ->route('patients.show', $death->patient_id)
            ->with('success', 'Death record created successfully.');
    }

    /**
     * Display the specified death record.
     */
    public function show(Death $death): View
    {
        return view('deaths.show', [
            'death' => $death->load(['patient', 'causeDiagnosis', 'recordedBy']),
        ]);
    }

    /**
     * Show the form for editing the specified death record.
     */
    public function edit(Death $death): View
    {
        return view('deaths.edit', [
            'death' => $death,
        ]);
    }

    /**
     * Update the specified death record.
     */
    public function update(UpdateDeathRequest $request, Death $death): RedirectResponse
    {
        $validated = $request->validated();

        $oldValues = $death->getOriginal();
        $death->update($validated);

        $this->audit->log('death_updated', 'deaths', $request, null, $death->id, $oldValues);

        return redirect()
            ->route('patients.show', $death->patient_id)
            ->with('success', 'Death record updated successfully.');
    }

    /**
     * Remove the specified death record.
     */
    public function destroy(Death $death): RedirectResponse
    {
        $patientId = $death->patient_id;
        $deathId = $death->id;
        $death->delete();

        $this->audit->log('death_deleted', 'deaths', request(), null, $deathId);

        return redirect()
            ->route('patients.show', $patientId)
            ->with('success', 'Death record deleted successfully.');
    }
}
