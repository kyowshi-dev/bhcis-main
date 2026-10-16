<?php

namespace Tests\Feature;

use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class PatientUpdateTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('permissions')->insertOrIgnore([
            ['name' => 'patients', 'description' => 'Access to Patients module', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'consultations', 'description' => 'Access to Consultations module', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('zones')->insert(['id' => 1, 'zone_number' => '1']);
    }

    private function createPatientFixture(): int
    {
        $householdId = DB::table('households')->insertGetId([
            'zone_id' => 1,
            'head_last_name' => 'Dela Cruz',
            'contact_number' => '09171234567',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('patients')->insertGetId([
            'household_id' => $householdId,
            'first_name' => 'Juana',
            'last_name' => 'Dela Cruz',
            'sex' => 'Female',
            'date_of_birth' => '1985-05-10',
            'civil_status' => 'Married',
            'mother_name' => 'Maria',
            'spouse_name' => 'Juan',
            'family_relationship' => 'Mother',
            'residential_address' => '1 Sta. Ana, Tagoloan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'first_name' => 'Maria',
            'middle_name' => 'Concepcion',
            'last_name' => 'Santos',
            'suffix' => '',
            'sex' => 'Female',
            'date_of_birth' => '1990-02-15',
            'blood_type' => 'O+',
            'civil_status' => 'Widow/er (Balo)',
            'mother_name' => 'Maria Santos',
            'spouse_name' => 'Juan Dela Cruz',
            'family_relationship' => 'Daughter',
            'residential_address' => '2 Sta. Ana, Tagoloan',
            'philhealth_no' => '123456789012',
            'is_philhealth_member' => true,
            'is_pcb_member' => false,
            'has_4ps' => false,
            'has_nhts' => false,
        ];
    }

    public function test_update_rejects_empty_civil_status(): void
    {
        $patientId = $this->createPatientFixture();

        $payload = array_merge($this->validPayload(), ['civil_status' => '']);

        $response = $this->actingAs($this->createUserWithPermissions(['patients']))
            ->from(route('patients.show', $patientId))
            ->put(route('patients.update', $patientId), $payload);

        $response->assertSessionHasErrors('civil_status');
        $this->assertSame('Married', Patient::findOrFail($patientId)->civil_status);
    }

    public function test_update_accepts_existing_legacy_civil_status_value(): void
    {
        $patientId = $this->createPatientFixture();

        $payload = array_merge($this->validPayload(), ['civil_status' => 'Married']);

        $this->actingAs($this->createUserWithPermissions(['patients']))
            ->put(route('patients.update', $patientId), $payload)
            ->assertRedirect(route('patients.show', $patientId))
            ->assertSessionHasNoErrors();

        $this->assertSame('Married', Patient::findOrFail($patientId)->civil_status);
    }

    public function test_update_rejects_civil_status_outside_allowed_options(): void
    {
        $patientId = $this->createPatientFixture();

        $payload = array_merge($this->validPayload(), ['civil_status' => 'It complicated']);

        $this->actingAs($this->createUserWithPermissions(['patients']))
            ->put(route('patients.update', $patientId), $payload)
            ->assertSessionHasErrors('civil_status');
    }

    public function test_update_rejects_family_relationship_outside_enum_options(): void
    {
        $patientId = $this->createPatientFixture();

        $payload = array_merge($this->validPayload(), ['family_relationship' => 'Cousin']);

        $this->actingAs($this->createUserWithPermissions(['patients']))
            ->put(route('patients.update', $patientId), $payload)
            ->assertSessionHasErrors('family_relationship');
    }

    public function test_patients_show_selects_legacy_civil_status_value_in_edit_form(): void
    {
        $patientId = $this->createPatientFixture();

        $this->actingAs($this->createUserWithPermissions(['patients']))
            ->get(route('patients.show', $patientId))
            ->assertOk()
            ->assertSee('value="Married" selected', false);
    }

    public function test_user_with_patients_permission_can_update_patient_card_fields(): void
    {
        $patientId = $this->createPatientFixture();

        $response = $this->actingAs($this->createUserWithPermissions(['patients']))
            ->put(route('patients.update', $patientId), $this->validPayload());

        $response->assertRedirect(route('patients.show', $patientId));
        $response->assertSessionHas('success');

        $patient = Patient::findOrFail($patientId);
        $this->assertSame('1990-02-15', $patient->date_of_birth->format('Y-m-d'));
        $this->assertSame('O+', $patient->blood_type);
        $this->assertSame('Widow/er (Balo)', $patient->civil_status);
        $this->assertSame('Maria Santos', $patient->mother_name);
        $this->assertSame('Juan Dela Cruz', $patient->spouse_name);
        $this->assertSame('Daughter', $patient->family_relationship);
        $this->assertSame('2 Sta. Ana, Tagoloan', $patient->residential_address);
    }

    public function test_update_rejects_future_birth_date(): void
    {
        $patientId = $this->createPatientFixture();

        $payload = array_merge($this->validPayload(), [
            'date_of_birth' => now()->addDay()->toDateString(),
        ]);

        $response = $this->actingAs($this->createUserWithPermissions(['patients']))
            ->from(route('patients.show', $patientId))
            ->put(route('patients.update', $patientId), $payload);

        $response->assertSessionHasErrors('date_of_birth');
        $this->assertSame(
            '1985-05-10',
            Patient::findOrFail($patientId)->date_of_birth->format('Y-m-d')
        );
    }

    public function test_update_requires_sex(): void
    {
        $patientId = $this->createPatientFixture();

        $payload = $this->validPayload();
        unset($payload['sex']);

        $response = $this->actingAs($this->createUserWithPermissions(['patients']))
            ->from(route('patients.show', $patientId))
            ->put(route('patients.update', $patientId), $payload);

        $response->assertSessionHasErrors('sex');
        $this->assertSame('Female', Patient::findOrFail($patientId)->sex);
    }

    public function test_user_without_patients_permission_cannot_update(): void
    {
        $patientId = $this->createPatientFixture();

        $this->actingAs($this->createUserWithPermissions(['zones']))
            ->put(route('patients.update', $patientId), $this->validPayload())
            ->assertForbidden();

        $this->assertSame(
            '1985-05-10',
            Patient::findOrFail($patientId)->date_of_birth->format('Y-m-d')
        );
    }

    public function test_zone_scoped_user_cannot_update_patient_outside_their_zone(): void
    {
        $patientId = $this->createPatientFixture();

        $this->actingAs($this->createUserWithPermissions(['patients', 'household']))
            ->put(route('patients.update', $patientId), $this->validPayload())
            ->assertForbidden();

        $this->assertSame(
            '1985-05-10',
            Patient::findOrFail($patientId)->date_of_birth->format('Y-m-d')
        );
    }

    public function test_patients_show_displays_birthdate_without_time(): void
    {
        $patientId = $this->createPatientFixture();

        $this->actingAs($this->createUserWithPermissions(['patients']))
            ->get(route('patients.show', $patientId))
            ->assertOk()
            ->assertSee('May 10, 1985')
            ->assertDontSee('1985-05-10 00:00:00')
            ->assertDontSee('00:00:00');
    }

    public function test_patients_show_renders_inline_edit_affordance_for_users_with_patients_permission(): void
    {
        $patientId = $this->createPatientFixture();

        $this->actingAs($this->createUserWithPermissions(['patients']))
            ->get(route('patients.show', $patientId))
            ->assertOk()
            ->assertSee('aria-label="Edit patient information"', false)
            ->assertSee('name="_method"', false)
            ->assertSee(route('patients.update', $patientId), false);
    }

    public function test_patients_show_hides_inline_edit_affordance_without_patients_permission(): void
    {
        $patientId = $this->createPatientFixture();

        $this->actingAs($this->createUserWithPermissions(['consultations']))
            ->get(route('patients.show', $patientId))
            ->assertOk()
            ->assertDontSee('aria-label="Edit patient information"', false);
    }
}
