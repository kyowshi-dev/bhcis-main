<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maternal_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->unsignedInteger('menarche_age')->nullable();
            $table->unsignedInteger('period_duration_days')->nullable();
            $table->unsignedInteger('cycle_interval_days')->nullable();
            $table->unsignedInteger('onset_sexual_intercourse_age')->nullable();
            $table->string('birth_control_method')->nullable();
            $table->enum('menopause', ['no', 'yes'])->default('no');
            $table->timestamps();

            $table->unique('patient_id');
        });

        Schema::create('pregnancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->enum('status', ['active', 'delivered', 'closed'])->default('active');
            $table->unsignedInteger('gravidity')->nullable();
            $table->unsignedInteger('parity')->nullable();
            $table->unsignedInteger('term')->nullable();
            $table->unsignedInteger('preterm')->nullable();
            $table->unsignedInteger('livebirth')->nullable();
            $table->unsignedInteger('abortion')->nullable();
            $table->date('lmp');
            $table->date('edc')->nullable();
            $table->unsignedInteger('aog_weeks')->nullable();
            $table->enum('syphilis_result', ['negative', 'positive'])->default('negative');
            $table->enum('penicillin', ['no', 'yes'])->default('no');
            $table->date('tt_date')->nullable();
            $table->boolean('iron_taken')->default(false);
            $table->text('others')->nullable();
            $table->json('risk_flags')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('health_workers')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('prenatal_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pregnancy_id')->constrained('pregnancies')->cascadeOnDelete();
            $table->foreignId('consultation_id')->nullable()->constrained('consultations')->nullOnDelete();
            $table->date('visit_date');
            $table->decimal('fundic_height_cm', 4, 1)->nullable();
            $table->unsignedInteger('fetal_heart_tone_bpm')->nullable();
            $table->date('next_visit_date')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('health_workers')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('family_planning_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->enum('type_of_client', ['new_acceptor', 'continuing_user', 'drop_out', 'others']);
            $table->string('method');
            $table->text('drop_out_reason')->nullable();
            $table->date('schedule_next_visit')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('recorded_by')->nullable()->constrained('health_workers')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('family_planning_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('family_planning_clients')->cascadeOnDelete();
            $table->foreignId('consultation_id')->nullable()->constrained('consultations')->nullOnDelete();
            $table->date('visit_date');
            $table->string('method');
            $table->date('schedule_next_visit')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('health_workers')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('postnatal_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('pregnancy_id')->nullable()->constrained('pregnancies')->nullOnDelete();
            $table->foreignId('consultation_id')->nullable()->constrained('consultations')->nullOnDelete();
            $table->enum('pregnancy_outcome', ['live_birth', 'stillbirth', 'abortion', 'others']);
            $table->unsignedInteger('prenatal_visits_completed')->nullable();
            $table->enum('place_delivered', ['home', 'health_center', 'hospital', 'other_facility']);
            $table->enum('mode_of_delivery', ['normal_vaginal', 'cesarean', 'vacuum_forceps', 'others']);
            $table->enum('attendant_at_birth', ['midwife', 'physician', 'nurse', 'traditional_birth_attendant', 'others']);
            $table->date('delivery_date');
            $table->time('delivery_time');
            $table->date('breastfeeding_date');
            $table->time('breastfeeding_time');
            $table->date('postpartum_24h_date')->nullable();
            $table->date('postpartum_7d_date')->nullable();
            $table->date('postpartum_14d_date')->nullable();
            $table->date('postpartum_28d_date')->nullable();
            $table->json('danger_signs_mother')->nullable();
            $table->json('danger_signs_baby')->nullable();
            $table->date('vitamin_a_date')->nullable();
            $table->date('iron_date')->nullable();
            $table->unsignedInteger('iron_count')->nullable();
            $table->string('child_last_name')->nullable();
            $table->string('child_first_name')->nullable();
            $table->string('child_middle_name')->nullable();
            $table->enum('child_sex', ['M', 'F'])->nullable();
            $table->decimal('child_birth_length_cm', 4, 1)->nullable();
            $table->decimal('child_birth_weight_kg', 5, 2)->nullable();
            $table->foreignId('child_patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('health_workers')->nullOnDelete();
            $table->timestamps();
        });

        // Add pregnancy_id FK to consultations now that pregnancies table exists
        Schema::table('consultations', function (Blueprint $table) {
            $table->foreign('pregnancy_id')->references('id')->on('pregnancies')->nullOnDelete();
            $table->index('pregnancy_id');
        });
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropForeign(['pregnancy_id']);
            $table->dropIndex(['pregnancy_id']);
            $table->dropColumn('pregnancy_id');
        });

        Schema::dropIfExists('postnatal_records');
        Schema::dropIfExists('family_planning_visits');
        Schema::dropIfExists('family_planning_clients');
        Schema::dropIfExists('prenatal_visits');
        Schema::dropIfExists('pregnancies');
        Schema::dropIfExists('maternal_profiles');
    }
};
