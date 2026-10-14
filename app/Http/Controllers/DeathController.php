<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeathRequest;
use App\Http\Requests\UpdateDeathRequest;
use App\Models\Death;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DeathController extends Controller
{
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

        $death->update($validated);

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
        $death->delete();

        return redirect()
            ->route('patients.show', $patientId)
            ->with('success', 'Death record deleted successfully.');
    }
}
