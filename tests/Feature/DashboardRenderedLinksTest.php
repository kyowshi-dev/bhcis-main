<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class DashboardRenderedLinksTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    public function test_doctor_dashboard_renders_with_pending_referral(): void
    {
        $doctor = $this->createUserWithNamedRole('Doctor', [
            'patients', 'consultations', 'medicines', 'dashboard_handouts',
        ]);

        [$patientId, $consultationId] = $this->createCompletedConsultationFixture();

        DB::table('outward_referrals')->insert([
            'consultation_id' => $consultationId,
            'destination_facility' => 'RHU Tagoloan',
            'pertinent_history' => 'Fever',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($doctor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Doctor Dashboard')
            ->assertSee('RHU Tagoloan')
            ->assertSee(route('consultations.show', $consultationId), false);
    }

    public function test_results_ready_panel_print_link_renders_on_doctor_dashboard(): void
    {
        $doctor = $this->createUserWithNamedRole('Doctor', [
            'patients', 'consultations', 'medicines', 'dashboard_handouts',
        ]);

        [$patientId, $consultationId] = $this->createCompletedConsultationFixture();

        $this->actingAs($doctor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('consultations.handout', $consultationId), false);
    }

    public function test_results_ready_panel_print_link_renders_on_nurse_dashboard(): void
    {
        $nurse = $this->createUserWithNamedRole('Nurse', [
            'patients', 'consultations', 'medicines', 'dashboard_handouts',
        ]);

        [$patientId, $consultationId] = $this->createCompletedConsultationFixture();

        $this->actingAs($nurse)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('consultations.handout', $consultationId), false);
    }

    /**
     * @return array{0: int, 1: int} patient id, consultation id
     */
    private function createCompletedConsultationFixture(): array
    {
        DB::table('zones')->insertOrIgnore(['id' => 1, 'zone_number' => '1']);
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

        $workerId = DB::table('health_workers')->insertGetId([
            'user_id' => DB::table('users')->orderByDesc('id')->value('id'),
            'first_name' => 'Test',
            'last_name' => 'Doctor',
            'role' => 'Doctor',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $consultationId = DB::table('consultations')->insertGetId([
            'patient_id' => $patientId,
            'worker_id' => $workerId,
            'status' => 'completed',
            'nature_of_visit' => 'Checkup',
            'mode_of_transaction' => 'Walk-in',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$patientId, $consultationId];
    }
}
