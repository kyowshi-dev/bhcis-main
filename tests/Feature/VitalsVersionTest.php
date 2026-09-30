<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class VitalsVersionTest extends TestCase
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

    public function test_editing_a_vitals_version_updates_the_row(): void
    {
        [$bhw, $consultationId] = $this->createConsultation();
        $vitalId = (int) DB::table('vitals')->where('consultation_id', $consultationId)->value('id');

        $this->actingAs($bhw)
            ->put("/consultations/{$consultationId}/vitals/{$vitalId}", [
                'bp_systolic' => 135,
                'bp_diastolic' => 88,
                'temperature' => 36.9,
                'weight' => 61.2,
                'height' => 166,
                'notes' => 'Corrected reading',
            ])
            ->assertRedirect(route('consultations.show', $consultationId))
            ->assertSessionHas('success');

        $vital = DB::table('vitals')->where('id', $vitalId)->first();

        $this->assertNotNull($vital);
        $this->assertSame(135, (int) $vital->bp_systolic);
        $this->assertSame(88, (int) $vital->bp_diastolic);
        $this->assertSame('Corrected reading', $vital->notes);
        $this->assertSame(1, DB::table('vitals')->where('consultation_id', $consultationId)->count());
    }

    public function test_editing_a_blank_vitals_version_is_rejected(): void
    {
        [$bhw, $consultationId] = $this->createConsultation();
        $vitalId = (int) DB::table('vitals')->where('consultation_id', $consultationId)->value('id');
        $original = DB::table('vitals')->where('id', $vitalId)->first();

        $this->actingAs($bhw)
            ->from(route('consultations.show', $consultationId))
            ->put("/consultations/{$consultationId}/vitals/{$vitalId}", [
                'bp_systolic' => '',
                'bp_diastolic' => '',
                'temperature' => '',
                'weight' => '',
                'height' => '',
                'notes' => '',
            ])
            ->assertRedirect(route('consultations.show', $consultationId))
            ->assertSessionHasErrors('bp_systolic')
            ->assertSessionHas('vitals_form', 'edit');

        $this->assertSame(
            $original->bp_systolic,
            DB::table('vitals')->where('id', $vitalId)->value('bp_systolic')
        );
    }

    public function test_editing_a_vital_from_another_consultation_returns_404(): void
    {
        [$bhw, $consultationId] = $this->createConsultation();
        [, $otherConsultationId] = $this->createConsultation();
        $foreignVitalId = (int) DB::table('vitals')->where('consultation_id', $otherConsultationId)->value('id');

        $this->actingAs($bhw)
            ->put("/consultations/{$consultationId}/vitals/{$foreignVitalId}", [
                'bp_systolic' => 130,
            ])
            ->assertNotFound();
    }

    public function test_retake_rejects_an_empty_submission(): void
    {
        [$bhw, $consultationId] = $this->createConsultation('doctor_review');

        $this->actingAs($bhw)
            ->from(route('consultations.show', $consultationId))
            ->post("/consultations/{$consultationId}/vitals/retake", [
                'bp_systolic' => '',
                'bp_diastolic' => '',
                'temperature' => '',
                'weight' => '',
                'height' => '',
                'notes' => '',
            ])
            ->assertRedirect(route('consultations.show', $consultationId))
            ->assertSessionHasErrors('bp_systolic')
            ->assertSessionHas('vitals_form', 'retake');

        $this->assertSame(1, DB::table('vitals')->where('consultation_id', $consultationId)->count());
    }

    public function test_retake_adds_a_new_clinical_version(): void
    {
        [$bhw, $consultationId] = $this->createConsultation('doctor_review');

        $this->actingAs($bhw)
            ->post("/consultations/{$consultationId}/vitals/retake", [
                'temperature' => 38.2,
                'notes' => 'Re-checked after rest',
            ])
            ->assertRedirect(route('consultations.show', $consultationId))
            ->assertSessionHas('success');

        $this->assertSame(2, DB::table('vitals')->where('consultation_id', $consultationId)->count());

        $latest = DB::table('vitals')
            ->where('consultation_id', $consultationId)
            ->orderByDesc('id')
            ->first();

        $this->assertSame('clinical', $latest->phase);
        $this->assertSame('Re-checked after rest', $latest->notes);
        $this->assertSame(
            'in_progress',
            DB::table('consultations')->where('id', $consultationId)->value('status')
        );
    }

    public function test_retake_is_blocked_before_clinical_review(): void
    {
        [$bhw, $consultationId] = $this->createConsultation('nurse_review');

        $this->actingAs($bhw)
            ->from(route('consultations.show', $consultationId))
            ->post("/consultations/{$consultationId}/vitals/retake", [
                'temperature' => 38.2,
            ])
            ->assertSessionHasErrors('consultation');

        $this->assertSame(1, DB::table('vitals')->where('consultation_id', $consultationId)->count());
    }

    public function test_show_page_renders_edit_modal_state_without_raw_directives(): void
    {
        [$bhw, $consultationId] = $this->createConsultation('doctor_review');

        $response = $this->actingAs($bhw)->get(route('consultations.show', $consultationId));

        $response->assertOk();
        $response->assertSee('modalMerge({', false);
        $response->assertSee('editVitalId:', false);
        $response->assertDontSee('@js(', false);
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createConsultation(string $status = 'nurse_review'): array
    {
        $user = $this->createWorkerUser('BHW');

        DB::table('zones')->insertOrIgnore(['id' => 1, 'zone_number' => '1']);
        $householdId = DB::table('households')->insertGetId([
            'zone_id' => 1,
            'family_name_head' => 'Test',
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

        $this->actingAs($user)->post("/patients/{$patientId}/consultations", [
            'mode_of_transaction' => 'Walk-in',
            'nature_of_visit' => 'Checkup',
            'purpose_of_visit' => 'General checkup',
            'chief_complaint' => 'Fever',
            'bp_systolic' => 120,
            'bp_diastolic' => 80,
            'temperature' => 37.5,
            'weight' => 60,
            'height' => 165,
        ])->assertRedirect();

        $consultationId = (int) DB::table('consultations')->where('patient_id', $patientId)->value('id');

        DB::table('consultations')->where('id', $consultationId)->update(['status' => $status]);

        return [$user, $consultationId];
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
