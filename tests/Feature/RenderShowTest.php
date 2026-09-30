<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class RenderShowTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('permissions')->insert([
            ['name' => 'patients', 'description' => 'p', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'consultations', 'description' => 'c', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function baseFixture(): array
    {
        $user = $this->createUserWithPermissions(['patients', 'consultations']);
        DB::table('zones')->insert(['id' => 1, 'zone_number' => '1']);
        $householdId = DB::table('households')->insertGetId(['zone_id' => 1, 'family_name_head' => 'T', 'created_at' => now(), 'updated_at' => now()]);
        $patientId = DB::table('patients')->insertGetId([
            'household_id' => $householdId, 'first_name' => 'J', 'last_name' => 'D', 'sex' => 'Female',
            'date_of_birth' => '1990-01-01', 'civil_status' => 'Single', 'employment_status' => 'E',
            'mother_name' => 'M', 'spouse_name' => 'N', 'family_relationship' => 'Mother',
            'residential_address' => 'X', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('health_workers')->insert(['user_id' => $user->id, 'first_name' => 'T', 'last_name' => 'R', 'role' => 'BHW', 'created_at' => now(), 'updated_at' => now()]);
        $consultationId = DB::table('consultations')->insertGetId([
            'patient_id' => $patientId, 'worker_id' => DB::table('health_workers')->where('user_id', $user->id)->value('id'),
            'status' => 'doctor_review', 'nature_of_visit' => 'Checkup', 'mode_of_transaction' => 'Walk-in',
            'complaint_text' => 'Fever', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return [$user, $consultationId];
    }

    public function test_render_with_vitals_row(): void
    {
        [$user, $consultationId] = $this->baseFixture();
        DB::table('vitals')->insert([
            'consultation_id' => $consultationId, 'phase' => 'triage',
            'bp_systolic' => '120', 'bp_diastolic' => '80', 'temperature_c' => 37.5,
            'weight_kg' => 60, 'height_cm' => 165, 'notes' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        fwrite(STDERR, "GET with vitals...\n");
        $response = $this->actingAs($user)->get("/consultations/{$consultationId}");
        fwrite(STDERR, 'status='.$response->getStatusCode()."\n");
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_render_without_vitals(): void
    {
        [$user, $consultationId] = $this->baseFixture();
        fwrite(STDERR, "GET without vitals...\n");
        $response = $this->actingAs($user)->get("/consultations/{$consultationId}");
        fwrite(STDERR, 'status='.$response->getStatusCode()."\n");
        $this->assertSame(200, $response->getStatusCode());
    }
}
