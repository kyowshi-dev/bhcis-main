<?php

namespace App\Services;

use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\Patient;
use Illuminate\Validation\ValidationException;

final class PatientService
{
    public static function ensureTransientHousehold(): Household
    {
        $transientHousehold = Household::query()
            ->where(function ($qb) {
                $qb->whereRaw('LOWER(head_last_name) LIKE ?', ['%transient%'])
                    ->orWhereRaw('LOWER(head_last_name) LIKE ?', ['%unmapped%']);
            })
            ->select(['id', 'head_last_name'])
            ->first();

        if (! $transientHousehold) {
            $transientHousehold = Household::create([
                'zone_id' => 1,
                'head_last_name' => 'Transient/Unmapped',
            ]);
        }

        return $transientHousehold;
    }

    /**
     * Register a new patient, optionally creating the household first.
     * Rolls back the created household and throws a ValidationException on duplicates.
     */
    public static function register(array $validated): Patient
    {
        $user = auth()->user();

        // Zone-scoped workers may attach patients to any household and may
        // create households in any zone. The enrolling worker is recorded in
        // patients.enrolled_by so they keep access to patients they enrolled
        // outside their assigned zones.

        // --- 1. HANDLE HOUSEHOLD CREATION OR SELECTION ---
        $householdId = $validated['household_id'] ?? null;
        $createdHousehold = null;

        if ((int) $validated['create_new_household'] === 1) {
            $createdHousehold = Household::create([
                'zone_id' => $validated['new_household_zone_id'],
                'head_first_name' => ucwords(strtolower(trim($validated['new_household_head_first_name']))),
                'head_last_name' => ucwords(strtolower(trim($validated['new_household_head_last_name']))),
                'contact_number' => ($validated['new_household_contact_number'] ?? null) !== null ? trim($validated['new_household_contact_number']) : null,
            ]);
            $householdId = $createdHousehold->id;
        }

        $zone = Household::with('zone')->find($householdId)?->zone;
        $zoneNumber = $zone ? $zone->zone_number : '';

        $residentialAddress = trim($zoneNumber).' Sta. Ana, Tagoloan';

        // --- 2. DUPLICATE CHECK ---
        // Prevents double-entry of the same person
        $exists = Patient::query()
            ->where('first_name', $validated['first_name'])
            ->where('last_name', $validated['last_name'])
            ->where('date_of_birth', $validated['date_of_birth'])
            ->exists();

        if ($exists) {
            // In case of duplicate, rollback household creation if we just created it
            if ($createdHousehold) {
                $createdHousehold->delete();
            }

            throw ValidationException::withMessages(['first_name' => 'This patient is already registered in the system!']);
        }

        // --- 3. INSERT PATIENT DATA (Sanitized) ---
        $patient = Patient::create([
            'household_id' => $householdId,
            'enrolled_by' => $user?->id,
            // Auto-Capitalize Names
            'first_name' => ucwords(strtolower($validated['first_name'])),
            'last_name' => ucwords(strtolower($validated['last_name'])),
            'middle_name' => ($validated['middle_name'] ?? null) ? ucfirst(strtolower($validated['middle_name'])) : null,

            'suffix' => $validated['suffix'] ?? null,
            'sex' => $validated['sex'],
            'date_of_birth' => $validated['date_of_birth'],
            'birth_place' => $validated['birth_place'] ?? null,
            'blood_type' => $validated['blood_type'] ?? null,
            'civil_status' => $validated['civil_status'] ?? null,
            'educational_attainment' => $validated['educational_attainment'] ?? null,
            'employment_status' => $validated['employment_status'] ?? null,

            'mother_name' => $validated['mother_name'],
            'spouse_name' => $validated['spouse_name'],
            'family_relationship' => $validated['family_relationship'],
            'residential_address' => $residentialAddress,
            'is_philhealth_member' => $validated['is_philhealth_member'],
            'status_type' => $validated['status_type'] ?? null,
            'philhealth_no' => $validated['philhealth_no'] ?? null,
            'membership_category' => $validated['membership_category'] ?? null,
            'is_pcb_member' => $validated['is_pcb_member'],

            'has_4ps' => $validated['has_4ps'],
            'has_nhts' => $validated['has_nhts'],
        ]);

        // --- 4. LINK OR CREATE HOUSEHOLD MEMBER ---
        self::linkOrCreateMember($patient, $validated, $householdId);

        return $patient;
    }

    private static function linkOrCreateMember(Patient $patient, array $validated, int $householdId): void
    {
        $memberId = $validated['household_member_id'] ?? null;

        if ($memberId) {
            // Link existing member
            $member = HouseholdMember::where('id', $memberId)
                ->where('household_id', $householdId)
                ->whereNull('deleted_at')
                ->first();

            if ($member) {
                $member->update(['patient_id' => $patient->id]);
            }

            return;
        }

        // Auto-create member from patient data
        $relationship = self::mapFamilyRelationship($validated['family_relationship']);

        HouseholdMember::create([
            'household_id' => $householdId,
            'patient_id' => $patient->id,
            'first_name' => $patient->first_name,
            'middle_name' => $patient->middle_name,
            'last_name' => $patient->last_name,
            'suffix' => $patient->suffix,
            'sex' => $patient->sex,
            'date_of_birth' => $patient->date_of_birth,
            'age' => null,
            'relationship' => $relationship,
        ]);
    }

    private static function mapFamilyRelationship(string $rel): string
    {
        return match ($rel) {
            'Father' => 'Father',
            'Mother' => 'Mother',
            'Son' => 'Son',
            'Daughter' => 'Daughter',
            default => 'Other',
        };
    }
}
