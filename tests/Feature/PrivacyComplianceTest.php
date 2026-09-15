<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Patient;
use App\Models\PrivacySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class PrivacyComplianceTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('zones')->insertOrIgnore([
            'id' => 1,
            'zone_number' => 'Zone 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createPatient(array $overrides = []): Patient
    {
        return Patient::create(array_merge([
            'household_id' => Household::create(['zone_id' => 1, 'family_name_head' => 'Dela Cruz'])->id,
            'first_name' => 'Maria',
            'last_name' => 'Dela Cruz',
            'sex' => 'Female',
            'date_of_birth' => '1990-05-10',
            'civil_status' => 'Married',
            'mother_name' => '',
            'spouse_name' => 'Juan Dela Cruz',
            'family_relationship' => 'Mother',
            'residential_address' => 'Zone 1 Sta. Ana',
        ], $overrides));
    }

    // --- Privacy Settings ---

    public function test_admin_can_view_privacy_settings(): void
    {
        $user = $this->createUserWithPermissions(['users']);

        $response = $this->actingAs($user)->get(route('privacy.index'));

        $response->assertStatus(200);
        $response->assertSee('Privacy Settings');
    }

    public function test_user_without_permission_cannot_view_privacy_settings(): void
    {
        $user = $this->createUserWithPermissions([]);

        $this->actingAs($user)->get(route('privacy.index'))->assertStatus(403);
    }

    public function test_admin_can_update_privacy_settings(): void
    {
        $user = $this->createUserWithPermissions(['users']);

        $response = $this->actingAs($user)->put(route('privacy.update'), [
            'privacy_policy_version' => '2.0',
            'data_retention_days' => '3650',
            'purpose_limitation' => ['Treatment', 'Research'],
        ]);

        $response->assertRedirect(route('privacy.index'));
        $response->assertSessionHas('success');

        $privacy = PrivacySetting::getActive();
        $this->assertEquals('2.0', $privacy->privacy_policy_version);
        $this->assertEquals(['Treatment', 'Research'], $privacy->purpose_limitation);
    }

    public function test_public_privacy_policy_page_is_accessible(): void
    {
        $response = $this->get(route('privacy.policy'));

        $response->assertStatus(200);
    }

    public function test_public_purposes_page_is_accessible(): void
    {
        $response = $this->get(route('privacy.purposes'));

        $response->assertStatus(200);
    }

    // --- Soft Deletes ---

    public function test_patient_model_uses_soft_deletes(): void
    {
        $patient = $this->createPatient();
        $patientId = $patient->id;

        $patient->delete();

        $this->assertSoftDeleted('patients', ['id' => $patientId]);
        $this->assertNull(Patient::find($patientId));
        $this->assertNotNull(Patient::withTrashed()->find($patientId));
    }

    public function test_admin_can_soft_delete_patient(): void
    {
        $user = $this->createUserWithPermissions(['patients']);
        $patient = $this->createPatient();

        $response = $this->actingAs($user)->delete(route('patients.destroy', $patient));

        $response->assertRedirect(route('patients.index'));
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('patients', ['id' => $patient->id]);
    }

    public function test_admin_can_restore_soft_deleted_patient(): void
    {
        $user = $this->createUserWithPermissions(['patients']);
        $patient = $this->createPatient();
        $patient->delete();

        $response = $this->actingAs($user)->post(route('patients.restore', $patient));

        $response->assertRedirect(route('patients.show', $patient));
        $response->assertSessionHas('success');
        $this->assertNotSoftDeleted('patients', ['id' => $patient->id]);
    }

    public function test_admin_can_force_delete_patient(): void
    {
        $user = $this->createUserWithPermissions(['patients']);
        $patient = $this->createPatient();
        $patientId = $patient->id;

        $response = $this->actingAs($user)->delete(route('patients.forceDestroy', $patient));

        $response->assertRedirect(route('patients.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('patients', ['id' => $patientId]);
    }
}
