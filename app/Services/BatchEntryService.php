<?php

namespace App\Services;

use App\Models\Household;
use App\Models\Patient;
use Illuminate\Validation\ValidationException;

final class BatchEntryService
{
    /**
     * Register a patient from batch entry.
     * Supports both selecting an existing household and creating a new one inline.
     * Includes duplicate detection.
     */
    public static function registerPatient(array $validated): Patient
    {
        $user = auth()->user();

        // --- 1. HANDLE HOUSEHOLD CREATION OR SELECTION ---
        $householdId = $validated['household_id'] ?? null;
        $createdHousehold = null;

        if ((int) ($validated['create_new_household'] ?? 0) === 1) {
            // Zone-scoped workers may create households in any zone (no restriction on creation).
            $createdHousehold = Household::create([
                'zone_id' => $validated['new_household_zone_id'],
                'family_name_head' => trim($validated['new_household_family_name_head']),
                'contact_number' => ($validated['new_household_contact_number'] ?? null) !== null ? trim($validated['new_household_contact_number']) : null,
            ]);
            $householdId = $createdHousehold->id;
        } elseif ($householdId !== null) {
            // Zone-scoped workers may only attach patients to households within their coverage.
            if ($user !== null && $user->isZoneScoped()) {
                if (! $user->canAccessHousehold((int) $householdId)) {
                    throw ValidationException::withMessages([
                        'household_id' => 'This household is outside your assigned zones.',
                    ]);
                }
            }
        }

        $household = Household::with('zone')->find($householdId);

        if (! $household) {
            throw ValidationException::withMessages([
                'household_id' => 'Selected household does not exist.',
            ]);
        }

        $zoneNumber = $household->zone->zone_number ?? '';
        $residentialAddress = trim($zoneNumber).' Sta. Ana, Tagoloan';

        // --- 2. DUPLICATE CHECK ---
        $exists = Patient::query()
            ->where('first_name', $validated['first_name'])
            ->where('last_name', $validated['last_name'])
            ->where('date_of_birth', $validated['date_of_birth'])
            ->exists();

        if ($exists) {
            // Rollback household creation if we just created it
            if ($createdHousehold) {
                $createdHousehold->delete();
            }

            throw ValidationException::withMessages([
                'first_name' => 'A patient with this name and birthdate already exists. Check for duplicates before entering.',
            ]);
        }

        // --- 3. INSERT PATIENT DATA ---
        return Patient::create([
            'household_id' => $householdId,
            'first_name' => ucwords(strtolower($validated['first_name'])),
            'last_name' => ucwords(strtolower($validated['last_name'])),
            'middle_name' => $validated['middle_name'] ? ucfirst(strtolower($validated['middle_name'])) : null,
            'suffix' => $validated['suffix'] ?? null,
            'sex' => $validated['sex'],
            'date_of_birth' => $validated['date_of_birth'],
            'birth_place' => $validated['birth_place'] ?? null,
            'blood_type' => $validated['blood_type'] ?? null,
            'civil_status' => $validated['civil_status'] ?? null,
            'educational_attainment' => $validated['educational_attainment'] ?? null,
            'employment_status' => $validated['employment_status'] ?? null,
            'mother_name' => $validated['mother_name'] ?? '',
            'spouse_name' => $validated['spouse_name'] ?? '',
            'family_relationship' => $validated['family_relationship'] ?? 'Others',
            'residential_address' => $residentialAddress,
            'is_philhealth_member' => $validated['is_philhealth_member'],
            'philhealth_no' => $validated['philhealth_no'] ?? null,
            'membership_category' => $validated['membership_category'] ?? null,
            'status_type' => $validated['status_type'] ?? null,
            'is_pcb_member' => $validated['is_pcb_member'],
            'has_4ps' => $validated['has_4ps'] ?? false,
            'has_nhts' => $validated['has_nhts'] ?? false,
        ]);
    }
}
