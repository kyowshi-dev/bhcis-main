<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vaccines_lookup', function (Blueprint $table) {
            $table->id();
            $table->string('vaccine_code')->unique();
            $table->string('vaccine_name');
            $table->string('description')->nullable();
            $table->enum('category', ['Child', 'Adult', 'Both']);
            $table->string('group_key', 50)->nullable()->index();
            $table->unsignedInteger('start_after_days')->nullable();
            $table->unsignedInteger('complete_before_days')->nullable();
            $table->unsignedInteger('repeat_months')->nullable();
            $table->unsignedTinyInteger('sort_order');
            $table->timestamps();
        });

        Schema::create('immunization_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('vaccine_id')->constrained('vaccines_lookup')->cascadeOnDelete();
            $table->unsignedTinyInteger('dose_number');
            $table->date('date_given');
            $table->decimal('temp_recorded', 4, 2)->nullable();
            $table->decimal('child_weight_kg', 5, 2)->nullable();
            $table->decimal('child_height_cm', 5, 2)->nullable();
            $table->foreignId('administered_by')->nullable()->constrained('health_workers');
            $table->text('notes')->nullable();
            $table->boolean('no_show')->default(false);
            $table->timestamp('no_show_at')->nullable();
            $table->timestamps();
        });

        Schema::create('vaccine_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vaccine_id')->constrained('vaccines_lookup')->cascadeOnDelete();
            $table->unsignedTinyInteger('dose_number');
            $table->unsignedInteger('min_age_days');
            $table->unsignedInteger('gap_days')->nullable();
            $table->boolean('requires_temp')->default(true);
            $table->timestamps();

            $table->unique(['vaccine_id', 'dose_number']);
        });

        Schema::create('immunization_status_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('vaccine_id')->constrained('vaccines_lookup')->cascadeOnDelete();
            $table->unsignedTinyInteger('dose_number')->nullable();
            $table->string('event_type');
            $table->date('event_date');
            $table->string('note', 500)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'vaccine_id', 'event_type'], 'status_events_vaccine_type_idx');
        });

        $this->seedVaccinesAndSchedules();
    }

    public function down(): void
    {
        Schema::dropIfExists('immunization_status_events');
        Schema::dropIfExists('vaccine_schedules');
        Schema::dropIfExists('immunization_records');
        Schema::dropIfExists('vaccines_lookup');
    }

    private function seedVaccinesAndSchedules(): void
    {
        $now = now();

        $vaccines = [
            ['vaccine_code' => 'BCG', 'vaccine_name' => 'BCG', 'description' => 'At birth (within 24 hours)', 'category' => 'Child', 'sort_order' => 1, 'group_key' => null, 'start_after_days' => 0, 'complete_before_days' => 365, 'repeat_months' => null],
            ['vaccine_code' => 'HEPA_B_24H', 'vaccine_name' => 'Hepa B (w/in 24 hrs)', 'description' => 'Birth dose within 24 hours', 'category' => 'Child', 'sort_order' => 2, 'group_key' => 'HEPA_B', 'start_after_days' => 0, 'complete_before_days' => 1, 'repeat_months' => null],
            ['vaccine_code' => 'HEPA_B_GT24', 'vaccine_name' => 'Hepa B (>= 24 hrs)', 'description' => 'Late birth dose - give as soon as possible', 'category' => 'Child', 'sort_order' => 3, 'group_key' => 'HEPA_B', 'start_after_days' => 2, 'complete_before_days' => null, 'repeat_months' => null],
            ['vaccine_code' => 'PENTA', 'vaccine_name' => 'PENTA', 'description' => '6, 10 and 14 weeks (DTwP-HepB-Hib)', 'category' => 'Child', 'sort_order' => 4, 'group_key' => null, 'start_after_days' => 42, 'complete_before_days' => 180, 'repeat_months' => null],
            ['vaccine_code' => 'OPV', 'vaccine_name' => 'OPV', 'description' => '6, 10 and 14 weeks (bivalent oral polio)', 'category' => 'Child', 'sort_order' => 5, 'group_key' => null, 'start_after_days' => 42, 'complete_before_days' => 180, 'repeat_months' => null],
            ['vaccine_code' => 'IPV', 'vaccine_name' => 'IPV', 'description' => '14 weeks - inactivated polio', 'category' => 'Child', 'sort_order' => 6, 'group_key' => null, 'start_after_days' => 98, 'complete_before_days' => 180, 'repeat_months' => null],
            ['vaccine_code' => 'PCV', 'vaccine_name' => 'PCV', 'description' => '6, 10 and 14 weeks (pneumococcal conjugate)', 'category' => 'Child', 'sort_order' => 7, 'group_key' => null, 'start_after_days' => 42, 'complete_before_days' => 180, 'repeat_months' => null],
            ['vaccine_code' => 'MCV_AMV', 'vaccine_name' => 'MCV (AMV)', 'description' => '9 months - measles containing vaccine', 'category' => 'Child', 'sort_order' => 8, 'group_key' => null, 'start_after_days' => 270, 'complete_before_days' => 365, 'repeat_months' => null],
            ['vaccine_code' => 'MCV_MMR', 'vaccine_name' => 'MCV (MMR)', 'description' => '12 months - measles, mumps, rubella', 'category' => 'Child', 'sort_order' => 9, 'group_key' => null, 'start_after_days' => 365, 'complete_before_days' => 730, 'repeat_months' => null],
            ['vaccine_code' => 'ROTA', 'vaccine_name' => 'ROTA', 'description' => '6 and 10 weeks - complete by 8 months', 'category' => 'Child', 'sort_order' => 10, 'group_key' => null, 'start_after_days' => 42, 'complete_before_days' => 240, 'repeat_months' => null],
            ['vaccine_code' => 'HEPA_A', 'vaccine_name' => 'Hepa A', 'description' => '12 months, second dose 6 months later', 'category' => 'Child', 'sort_order' => 11, 'group_key' => null, 'start_after_days' => 365, 'complete_before_days' => null, 'repeat_months' => null],
            ['vaccine_code' => 'PNEUMONIA', 'vaccine_name' => 'Pneumonia', 'description' => 'As needed - single dose', 'category' => 'Adult', 'sort_order' => 12, 'group_key' => null, 'start_after_days' => 0, 'complete_before_days' => null, 'repeat_months' => null],
            ['vaccine_code' => 'FLU', 'vaccine_name' => 'Influenza', 'description' => 'Annual - adults', 'category' => 'Adult', 'sort_order' => 13, 'group_key' => null, 'start_after_days' => 0, 'complete_before_days' => null, 'repeat_months' => 12],
            ['vaccine_code' => 'PNEUMOCOCCAL', 'vaccine_name' => 'Pneumococcal', 'description' => 'Adult schedule - as needed', 'category' => 'Adult', 'sort_order' => 14, 'group_key' => null, 'start_after_days' => 0, 'complete_before_days' => null, 'repeat_months' => null],
        ];

        foreach ($vaccines as $vaccine) {
            DB::table('vaccines_lookup')->updateOrInsert(
                ['vaccine_code' => $vaccine['vaccine_code']],
                array_merge($vaccine, ['created_at' => $now, 'updated_at' => $now])
            );
        }

        $schedules = [
            ['BCG', [[1, 0, null]]],
            ['HEPA_B_24H', [[1, 0, null]]],
            ['HEPA_B_GT24', [[1, 2, null]]],
            ['PENTA', [[1, 42, 28], [2, 70, 28], [3, 98, null]]],
            ['OPV', [[1, 42, 28], [2, 70, 28], [3, 98, null]]],
            ['IPV', [[1, 98, null]]],
            ['PCV', [[1, 42, 28], [2, 70, 28], [3, 98, null]]],
            ['MCV_AMV', [[1, 270, null]]],
            ['MCV_MMR', [[1, 365, null]]],
            ['ROTA', [[1, 42, 28], [2, 70, null]]],
            ['HEPA_A', [[1, 365, 182], [2, 547, null]]],
            ['PNEUMONIA', [[1, 0, null]]],
            ['FLU', [[1, 0, null]]],
            ['PNEUMOCOCCAL', [[1, 0, null]]],
        ];

        foreach ($schedules as [$code, $doses]) {
            $vaccineId = DB::table('vaccines_lookup')->where('vaccine_code', $code)->value('id');
            $category = DB::table('vaccines_lookup')->where('id', $vaccineId)->value('category');

            foreach ($doses as [$doseNumber, $minAgeDays, $gapDays]) {
                DB::table('vaccine_schedules')->insert([
                    'vaccine_id' => $vaccineId,
                    'dose_number' => $doseNumber,
                    'min_age_days' => $minAgeDays,
                    'gap_days' => $gapDays,
                    'requires_temp' => $category === 'Child',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
};
