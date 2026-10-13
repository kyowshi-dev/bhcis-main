<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class FinalizeConsultationTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('permissions')->insert([
            ['name' => 'patients', 'description' => 'Access to Patients module', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'consultations', 'description' => 'Access to Consultations module', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_finalize_is_blocked_when_prescription_exists_without_diagnosis(): void
    {
        [$doctor, $consultationId] = $this->createDoctorReviewFixture();
        $this->addPrescription($consultationId);

        $this->actingAs($doctor)
            ->from(route('consultations.show', $consultationId))
            ->post(route('consultations.finalize', $consultationId), [
                'refer_to_higher_facility' => 0,
            ])
            ->assertRedirect(route('consultations.show', $consultationId))
            ->assertSessionHasErrors([
                'clinical' => 'A prescription requires a diagnosis. Add a diagnosis before finalizing the consultation.',
            ]);

        $this->assertSame('doctor_review', DB::table('consultations')->where('id', $consultationId)->value('status'));
    }

    public function test_finalize_is_blocked_when_neither_diagnosis_nor_prescription_exists(): void
    {
        [$doctor, $consultationId] = $this->createDoctorReviewFixture();

        $this->actingAs($doctor)
            ->from(route('consultations.show', $consultationId))
            ->post(route('consultations.finalize', $consultationId), [
                'refer_to_higher_facility' => 0,
            ])
            ->assertSessionHasErrors('clinical');

        $this->assertSame('doctor_review', DB::table('consultations')->where('id', $consultationId)->value('status'));
    }

    public function test_finalize_succeeds_with_diagnosis_only(): void
    {
        [$doctor, $consultationId] = $this->createDoctorReviewFixture();
        $this->addDiagnosis($consultationId);

        $this->actingAs($doctor)
            ->post(route('consultations.finalize', $consultationId), [
                'refer_to_higher_facility' => 0,
            ])
            ->assertRedirect(route('consultations.show', $consultationId));

        $this->assertSame('completed', DB::table('consultations')->where('id', $consultationId)->value('status'));
    }

    public function test_finalize_succeeds_with_diagnosis_and_prescription(): void
    {
        [$doctor, $consultationId] = $this->createDoctorReviewFixture();
        $this->addDiagnosis($consultationId);
        $this->addPrescription($consultationId);

        $this->actingAs($doctor)
            ->post(route('consultations.finalize', $consultationId), [
                'refer_to_higher_facility' => 0,
            ])
            ->assertRedirect(route('consultations.show', $consultationId));

        $this->assertSame('completed', DB::table('consultations')->where('id', $consultationId)->value('status'));
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createDoctorReviewFixture(): array
    {
        $bhw = $this->createWorkerUser('BHW');

        DB::table('zones')->insert(['id' => 1, 'zone_number' => '1']);
        $householdId = DB::table('households')->insertGetId([
            'zone_id' => 1,
            'head_last_name' => 'Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $patientId = DB::table('patients')->insertGetId([
            'household_id' => $householdId,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'sex' => 'Female',
            'date_of_birth' => '1990-01-01',
            'civil_status' => 'Single',
            'employment_status' => 'Employed',
            'mother_name' => 'Jane Senior',
            'spouse_name' => 'N/A',
            'family_relationship' => 'Mother',
            'residential_address' => 'Sta. Ana, Tagoloan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $bhwWorkerId = (int) DB::table('health_workers')->where('user_id', $bhw->id)->value('id');

        $consultationId = DB::table('consultations')->insertGetId([
            'patient_id' => $patientId,
            'worker_id' => $bhwWorkerId,
            'status' => 'doctor_review',
            'nature_of_visit' => 'Checkup',
            'mode_of_transaction' => 'Walk-in',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $doctor = $this->createWorkerUser('Doctor');

        return [$doctor, $consultationId];
    }

    private function addDiagnosis(int $consultationId, ?string $customName = 'Hypertension'): void
    {
        $diagnosedBy = (int) DB::table('health_workers')
            ->where('role', 'Doctor')
            ->value('id');

        DB::table('diagnosis_records')->insert([
            'consultation_id' => $consultationId,
            'custom_diagnosis_name' => $customName,
            'diagnosed_by' => $diagnosedBy,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function addPrescription(int $consultationId): void
    {
        $medicineId = DB::table('medicines_lookup')->insertGetId([
            'name' => 'Paracetamol 500mg',
            'form' => 'tablet',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('prescriptions')->insert([
            'consultation_id' => $consultationId,
            'medicine_id' => $medicineId,
            'dosage' => '1 tab',
            'route' => 'PO',
            'frequency' => 'TID (3 times daily)',
            'duration' => '5 days',
            'quantity' => 15,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createWorkerUser(string $role): User
    {
        $user = $this->createUserWithPermissions(['patients', 'consultations']);

        DB::table('health_workers')->insert([
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => $role,
            'role' => $role,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $user;
    }
}
