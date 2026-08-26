<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->string('role_name')->unique();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->foreignId('role_id')->nullable()->constrained('user_roles')->nullOnDelete();
            $table->string('profile_photo_path')->nullable();
            $table->text('bio')->nullable();
            $table->string('email')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('health_workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('role');
            $table->string('contact_number')->nullable();
            $table->timestamps();
        });

        Schema::create('diagnosis_lookup', function (Blueprint $table) {
            $table->id();
            $table->string('diagnosis_code')->unique();
            $table->string('diagnosis_name');
            $table->string('category')->nullable();
        });

        Schema::create('medicines_lookup', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('form')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->string('zone_number')->unique();
            $table->foreignId('assigned_worker_id')->nullable()->constrained('health_workers');
            $table->timestamps();
        });

        Schema::create('households', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('zones');
            $table->string('family_name_head');
            $table->string('contact_number')->nullable();
            $table->timestamps();
        });

        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained('households')->restrictOnDelete();

            $table->string('last_name');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('suffix')->nullable();
            $table->enum('sex', ['Male', 'Female']);
            $table->date('date_of_birth');
            $table->decimal('birth_weight', 5, 2)->nullable();
            $table->string('birth_place')->nullable();
            $table->string('blood_type')->nullable();

            $table->string('civil_status');
            $table->string('educational_attainment')->nullable();
            $table->string('employment_status')->nullable();
            $table->boolean('has_4ps')->default(false);
            $table->boolean('has_nhts')->default(false);
            $table->boolean('is_immunization_enrolled')->default(false);

            $table->string('mother_name')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('father_name')->nullable();
            $table->foreignId('mother_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->string('spouse_name')->nullable();
            $table->enum('family_relationship', ['Father', 'Son', 'Mother', 'Daughter', 'Others'])->nullable();
            $table->string('residential_address')->nullable();
            $table->enum('is_philhealth_member', ['y', 'n'])->default('n');
            $table->enum('status_type', ['Member', 'Dependent'])->nullable();
            $table->text('philhealth_no')->nullable();
            $table->enum('membership_category', ['FE - Private', 'FE - Government', 'IE', 'Others'])->nullable();
            $table->enum('is_pcb_member', ['y', 'n'])->default('n');

            $table->timestamps();

            $table->unique(['first_name', 'last_name', 'middle_name', 'date_of_birth'], 'unique_patient_record');
            $table->index('is_immunization_enrolled');
            $table->index('mother_id');

            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->fullText(['last_name', 'first_name']);
            }
        });

        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients');
            $table->foreignId('worker_id')->constrained('health_workers');
            $table->enum('status', ['triage', 'nurse_review', 'doctor_review', 'in_progress', 'completed', 'referred']);
            $table->timestamp('nurse_validated_at')->nullable();
            $table->foreignId('nurse_validated_by')->nullable()->constrained('health_workers')->nullOnDelete();
            $table->foreignId('attending_doctor_id')->nullable()->constrained('health_workers')->nullOnDelete();
            $table->unsignedBigInteger('pregnancy_id')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->text('complaint_text')->nullable();
            $table->string('nature_of_visit')->nullable();
            $table->text('notes')->nullable();
            $table->string('mode_of_transaction')->nullable();
            $table->string('purpose_of_visit', 50)->nullable();
            $table->string('referred_from')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('vitals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained('consultations')->cascadeOnDelete();
            $table->string('phase')->default('triage');
            $table->foreignId('captured_by')->nullable()->constrained('health_workers')->nullOnDelete();
            $table->string('bp_systolic')->nullable();
            $table->string('bp_diastolic')->nullable();
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->decimal('height_cm', 5, 2)->nullable();
            $table->decimal('temperature_c', 4, 2)->nullable();
            $table->decimal('bmi', 4, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('diagnosis_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained('consultations');
            $table->foreignId('diagnosis_id')->nullable()->constrained('diagnosis_lookup');
            $table->string('custom_diagnosis_name', 255)->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('diagnosed_by')->constrained('health_workers');
            $table->timestamps();
        });

        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained('consultations');
            $table->foreignId('medicine_id')->nullable()->constrained('medicines_lookup');
            $table->string('custom_medicine_name', 255)->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->string('dosage');
            $table->string('route', 50)->nullable();
            $table->string('frequency');
            $table->string('duration');
            $table->string('instructions', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('action');
            $table->string('table_name');
            $table->unsignedBigInteger('record_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('application_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        $this->seedApplicationSettings();
        $this->seedRoles();
    }

    public function down(): void
    {
        Schema::dropIfExists('application_settings');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('diagnosis_records');
        Schema::dropIfExists('vitals');
        Schema::dropIfExists('consultations');
        Schema::dropIfExists('patients');
        Schema::dropIfExists('households');
        Schema::dropIfExists('zones');
        Schema::dropIfExists('medicines_lookup');
        Schema::dropIfExists('diagnosis_lookup');
        Schema::dropIfExists('health_workers');
        Schema::dropIfExists('users');
        Schema::dropIfExists('user_roles');
    }

    private function seedRoles(): void
    {
        $roles = [
            ['id' => 1, 'role_name' => 'Admin'],
            ['id' => 2, 'role_name' => 'Nurse'],
            ['id' => 3, 'role_name' => 'Midwife'],
            ['id' => 4, 'role_name' => 'BHW'],
            ['id' => 5, 'role_name' => 'Doctor'],
        ];

        DB::table('user_roles')->insertOrIgnore($roles);
    }

    private function seedApplicationSettings(): void
    {
        $now = now();

        $settings = [
            ['key' => 'session_timeout', 'value' => '120'],
            ['key' => 'login_max_attempts', 'value' => '5'],
            ['key' => 'lockout_duration_minutes', 'value' => '15'],
            ['key' => 'password_min_length', 'value' => '8'],
            ['key' => 'password_require_uppercase', 'value' => '1'],
            ['key' => 'password_require_number', 'value' => '1'],
            ['key' => 'password_require_symbol', 'value' => '0'],
        ];

        foreach ($settings as $setting) {
            DB::table('application_settings')->updateOrInsert(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
};
