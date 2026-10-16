<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class ConsultationEditTest extends TestCase
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

    public function test_edit_page_renders(): void
    {
        [$doctor, $consultationId] = $this->createFixture();

        $this->actingAs($doctor)
            ->get("/consultations/{$consultationId}/edit")
            ->assertOk()
            ->assertSee('Edit Consultation')
            ->assertSee('Quick Notes')
            ->assertSee('Diagnoses and prescriptions are managed on the consultation record.')
            ->assertDontSee('editDiagnosis(')
            ->assertDontSee('editPrescription(')
            ->assertDontSee('Add Prescription');
    }

    public function test_edit_page_shows_the_attending_health_worker(): void
    {
        [$doctor, $consultationId] = $this->createFixture();

        $this->actingAs($doctor)
            ->get("/consultations/{$consultationId}/edit")
            ->assertOk()
            ->assertSee('Test Doctor')
            ->assertDontSee('Not assigned');
    }

    public function test_renaming_a_lookup_linked_diagnosis_is_visible_after_save(): void
    {
        [$doctor, $consultationId] = $this->createFixture();
        $lookupId = $this->createDiagnosisLookup('J06.9', 'Acute upper respiratory infection');
        $diagnosisId = $this->createLinkedDiagnosis($consultationId, $lookupId);

        $this->actingAs($doctor)
            ->get("/consultations/{$consultationId}")
            ->assertSee('Acute upper respiratory infection');

        $this->actingAs($doctor)->putJson("/consultations/{$consultationId}/diagnoses/{$diagnosisId}", [
            'diagnosis_name' => 'Acute viral pharyngitis',
            'diagnosis_id' => null,
            'remarks' => 'Confirmed on exam',
        ])->assertOk();

        $this->assertDatabaseHas('diagnosis_records', [
            'id' => $diagnosisId,
            'diagnosis_id' => null,
            'custom_diagnosis_name' => 'Acute viral pharyngitis',
        ]);

        $this->actingAs($doctor)
            ->get("/consultations/{$consultationId}")
            ->assertOk()
            ->assertSee('Acute viral pharyngitis')
            ->assertDontSee('Acute upper respiratory infection');
    }

    public function test_saving_an_unchanged_lookup_linked_diagnosis_keeps_the_icd_link(): void
    {
        [$doctor, $consultationId] = $this->createFixture();
        $lookupId = $this->createDiagnosisLookup('J06.9', 'Acute upper respiratory infection');
        $diagnosisId = $this->createLinkedDiagnosis($consultationId, $lookupId);

        $this->actingAs($doctor)->putJson("/consultations/{$consultationId}/diagnoses/{$diagnosisId}", [
            'diagnosis_name' => 'Acute upper respiratory infection',
        ])->assertOk();

        $this->assertDatabaseHas('diagnosis_records', [
            'id' => $diagnosisId,
            'diagnosis_id' => $lookupId,
            'custom_diagnosis_name' => null,
        ]);
    }

    public function test_renaming_a_lookup_linked_prescription_is_visible_after_save(): void
    {
        [$doctor, $consultationId] = $this->createFixture();
        $medicineId = $this->createMedicineLookup('Paracetamol 500mg tablet', 'Tablet');
        $prescriptionId = $this->createLinkedPrescription($consultationId, $medicineId);

        $this->actingAs($doctor)
            ->get("/consultations/{$consultationId}")
            ->assertSee('Paracetamol 500mg tablet');

        $this->actingAs($doctor)->putJson("/consultations/{$consultationId}/prescriptions/{$prescriptionId}", [
            'medicine_name' => 'Biogesic 500mg tablet',
            'medicine_id' => null,
            'dosage' => '1 tab',
            'route' => 'PO',
            'frequency' => 'TID',
            'duration' => '3 days',
            'quantity' => 9,
            'instructions' => 'After meals',
        ])->assertOk();

        $this->assertDatabaseHas('prescriptions', [
            'id' => $prescriptionId,
            'medicine_id' => null,
            'custom_medicine_name' => 'Biogesic 500mg tablet',
        ]);

        $this->actingAs($doctor)
            ->get("/consultations/{$consultationId}")
            ->assertOk()
            ->assertSee('Biogesic 500mg tablet')
            ->assertDontSee('Paracetamol 500mg tablet');
    }

    public function test_saving_an_unchanged_lookup_linked_prescription_keeps_the_medicine_link(): void
    {
        [$doctor, $consultationId] = $this->createFixture();
        $medicineId = $this->createMedicineLookup('Paracetamol 500mg tablet', 'Tablet');
        $prescriptionId = $this->createLinkedPrescription($consultationId, $medicineId);

        $this->actingAs($doctor)->putJson("/consultations/{$consultationId}/prescriptions/{$prescriptionId}", [
            'medicine_name' => 'Paracetamol 500mg tablet',
            'dosage' => '1 tab',
            'route' => 'PO',
            'frequency' => 'TID',
            'duration' => '3 days',
            'quantity' => 9,
        ])->assertOk();

        $this->assertDatabaseHas('prescriptions', [
            'id' => $prescriptionId,
            'medicine_id' => $medicineId,
            'custom_medicine_name' => null,
        ]);
    }

    public function test_custom_diagnosis_added_from_edit_page_renders_on_show_page(): void
    {
        [$doctor, $consultationId] = $this->createFixture();

        $this->actingAs($doctor)->postJson("/consultations/{$consultationId}/edit-diagnosis", [
            'diagnosis_name' => 'Dengue fever, unspecified',
            'remarks' => 'NSAIDs avoided',
        ])->assertOk();

        $this->actingAs($doctor)
            ->get("/consultations/{$consultationId}")
            ->assertOk()
            ->assertSee('Dengue fever, unspecified')
            ->assertSee('NSAIDs avoided');
    }

    public function test_custom_prescription_added_from_edit_page_renders_on_show_page(): void
    {
        [$doctor, $consultationId] = $this->createFixture();

        $this->actingAs($doctor)->postJson("/consultations/{$consultationId}/edit-prescription", [
            'medicine_name' => 'Homemade ORS solution',
            'dosage' => '200 mL',
            'route' => 'PO',
            'frequency' => 'After each loose stool',
            'duration' => '3 days',
            'quantity' => 6,
            'instructions' => '',
        ])->assertOk();

        $this->actingAs($doctor)
            ->get("/consultations/{$consultationId}")
            ->assertOk()
            ->assertSee('Homemade ORS solution');
    }

    public function test_show_page_shows_diagnosis_remarks(): void
    {
        [$doctor, $consultationId] = $this->createFixture();
        $lookupId = $this->createDiagnosisLookup('A09', 'Infection of unspecified site');
        $diagnosisId = $this->createLinkedDiagnosis($consultationId, $lookupId, 'Severe dehydration');

        $this->actingAs($doctor)
            ->get("/consultations/{$consultationId}")
            ->assertOk()
            ->assertSee('Severe dehydration');
    }

    public function test_update_diagnosis_from_edit(): void
    {
        [$doctor, $consultationId] = $this->createFixture();
        $diagnosisId = $this->createDiagnosis($consultationId);

        $this->actingAs($doctor)->putJson("/consultations/{$consultationId}/diagnoses/{$diagnosisId}", [
            'diagnosis_name' => 'Updated diagnosis',
            'remarks' => 'Updated remarks',
        ])->assertOk();

        $this->assertDatabaseHas('diagnosis_records', [
            'id' => $diagnosisId,
            'custom_diagnosis_name' => 'Updated diagnosis',
            'remarks' => 'Updated remarks',
        ]);
    }

    public function test_update_prescription_from_edit(): void
    {
        [$doctor, $consultationId] = $this->createFixture();
        $prescriptionId = $this->createPrescription($consultationId);

        $this->actingAs($doctor)->putJson("/consultations/{$consultationId}/prescriptions/{$prescriptionId}", [
            'medicine_name' => 'Paracetamol 500mg',
            'dosage' => '1 tab',
            'route' => 'PO',
            'frequency' => 'TID',
            'duration' => '3 days',
            'quantity' => 9,
            'instructions' => 'After meals',
        ])->assertOk();

        $this->assertDatabaseHas('prescriptions', [
            'id' => $prescriptionId,
            'custom_medicine_name' => 'Paracetamol 500mg',
            'frequency' => 'TID',
        ]);
    }

    public function test_add_diagnosis_from_edit_page(): void
    {
        [$doctor, $consultationId] = $this->createFixture();

        $this->actingAs($doctor)->postJson("/consultations/{$consultationId}/edit-diagnosis", [
            'diagnosis_name' => 'Common cold',
            'remarks' => '',
        ])->assertOk();

        $this->assertDatabaseHas('diagnosis_records', [
            'consultation_id' => $consultationId,
            'custom_diagnosis_name' => 'Common cold',
        ]);
    }

    public function test_add_prescription_from_edit_page(): void
    {
        [$doctor, $consultationId] = $this->createFixture();

        $this->actingAs($doctor)->postJson("/consultations/{$consultationId}/edit-prescription", [
            'medicine_name' => 'Paracetamol 500mg',
            'dosage' => '1 tab',
            'route' => 'PO',
            'frequency' => 'TID',
            'duration' => '3 days',
            'quantity' => 9,
            'instructions' => '',
        ])->assertOk();

        $this->assertDatabaseHas('prescriptions', [
            'consultation_id' => $consultationId,
            'custom_medicine_name' => 'Paracetamol 500mg',
        ]);
    }

    public function test_admin_can_modify_a_prescription(): void
    {
        [$admin, $consultationId] = $this->createFixture('Administrator');
        $prescriptionId = $this->createPrescription($consultationId);

        $this->actingAs($admin)->putJson("/consultations/{$consultationId}/prescriptions/{$prescriptionId}", [
            'medicine_name' => 'Paracetamol 500mg',
            'dosage' => '1 tab',
            'route' => 'PO',
            'frequency' => 'QID',
            'duration' => '3 days',
            'quantity' => 12,
            'instructions' => 'After meals',
        ])->assertOk();

        $this->assertDatabaseHas('prescriptions', [
            'id' => $prescriptionId,
            'frequency' => 'QID',
            'quantity' => 12,
        ]);
    }

    public function test_admin_can_add_and_delete_a_prescription(): void
    {
        [$admin, $consultationId] = $this->createFixture('Administrator');

        $this->actingAs($admin)->postJson("/consultations/{$consultationId}/edit-prescription", [
            'medicine_name' => 'Biogesic 500mg tablet',
            'dosage' => '1 tab',
            'route' => 'PO',
            'frequency' => 'TID',
            'duration' => '3 days',
            'quantity' => 9,
            'instructions' => '',
        ])->assertOk();

        $prescriptionId = (int) DB::table('prescriptions')
            ->where('consultation_id', $consultationId)
            ->where('custom_medicine_name', 'Biogesic 500mg tablet')
            ->value('id');

        $this->assertNotSame(0, $prescriptionId);

        $this->actingAs($admin)
            ->deleteJson("/consultations/{$consultationId}/prescriptions/{$prescriptionId}")
            ->assertOk();

        $this->assertSoftDeleted('prescriptions', ['id' => $prescriptionId]);
    }

    public function test_non_doctor_prescription_write_is_rejected_with_the_actual_reason(): void
    {
        [$bhw, $consultationId] = $this->createFixture('BHW');
        $prescriptionId = $this->createPrescription($consultationId);

        $this->actingAs($bhw)->putJson("/consultations/{$consultationId}/prescriptions/{$prescriptionId}", [
            'medicine_name' => 'Paracetamol 500mg',
            'dosage' => '1 tab',
            'route' => 'PO',
            'frequency' => 'TID',
            'duration' => '3 days',
            'quantity' => 9,
        ])->assertStatus(403)->assertJson([
            'message' => 'Only doctors and administrators can modify prescriptions.',
        ]);
    }

    public function test_show_page_hides_prescription_add_from_non_doctors(): void
    {
        [$bhw, $consultationId] = $this->createFixture('BHW');
        $this->createPrescription($consultationId);

        $this->actingAs($bhw)
            ->get("/consultations/{$consultationId}")
            ->assertOk()
            ->assertDontSee('title="P - Add prescription"', false)
            ->assertDontSee('Add first prescription');
    }

    public function test_show_page_shows_prescription_add_to_admin(): void
    {
        [$admin, $consultationId] = $this->createFixture('Administrator');
        $this->createPrescription($consultationId);

        $this->actingAs($admin)
            ->get("/consultations/{$consultationId}")
            ->assertOk()
            ->assertSee('title="P - Add prescription"', false);
    }

    public function test_update_notes_from_edit_page(): void
    {
        [$doctor, $consultationId] = $this->createFixture();

        $this->actingAs($doctor)->put("/consultations/{$consultationId}", [
            '_token' => csrf_token(),
            'notes' => 'Follow-up in one week.',
        ])->assertRedirect(route('consultations.show', $consultationId));

        $this->assertDatabaseHas('consultations', [
            'id' => $consultationId,
            'notes' => 'Follow-up in one week.',
        ]);
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createFixture(string $role = 'Doctor'): array
    {
        $user = $this->createWorkerUser($role);

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

        $workerId = (int) DB::table('health_workers')->where('user_id', $user->id)->value('id');

        $consultationId = DB::table('consultations')->insertGetId([
            'patient_id' => $patientId,
            'worker_id' => $workerId,
            'status' => 'doctor_review',
            'nature_of_visit' => 'Checkup',
            'mode_of_transaction' => 'Walk-in',
            'complaint_text' => 'Fever for three days',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user, $consultationId];
    }

    private function createDiagnosis(int $consultationId): int
    {
        return DB::table('diagnosis_records')->insertGetId([
            'consultation_id' => $consultationId,
            'diagnosis_id' => null,
            'custom_diagnosis_name' => 'Original diagnosis',
            'remarks' => 'Original remarks',
            'diagnosed_by' => $this->workerId($consultationId),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createDiagnosisLookup(string $code, string $name): int
    {
        return DB::table('diagnosis_lookup')->insertGetId([
            'diagnosis_code' => $code,
            'diagnosis_name' => $name,
        ]);
    }

    private function createMedicineLookup(string $name, string $form): int
    {
        return DB::table('medicines_lookup')->insertGetId([
            'name' => $name,
            'form' => $form,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createLinkedDiagnosis(int $consultationId, int $lookupId, ?string $remarks = null): int
    {
        return DB::table('diagnosis_records')->insertGetId([
            'consultation_id' => $consultationId,
            'diagnosis_id' => $lookupId,
            'custom_diagnosis_name' => null,
            'remarks' => $remarks,
            'diagnosed_by' => $this->workerId($consultationId),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createPrescription(int $consultationId): int
    {
        return DB::table('prescriptions')->insertGetId([
            'consultation_id' => $consultationId,
            'medicine_id' => null,
            'custom_medicine_name' => 'Original medicine',
            'dosage' => '1 tab',
            'route' => 'PO',
            'frequency' => 'BD',
            'duration' => '5 days',
            'quantity' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createLinkedPrescription(int $consultationId, int $medicineId): int
    {
        return DB::table('prescriptions')->insertGetId([
            'consultation_id' => $consultationId,
            'medicine_id' => $medicineId,
            'custom_medicine_name' => null,
            'dosage' => '1 tab',
            'route' => 'PO',
            'frequency' => 'TID',
            'duration' => '3 days',
            'quantity' => 9,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function workerId(int $consultationId): int
    {
        return (int) DB::table('consultations')->where('id', $consultationId)->value('worker_id');
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
