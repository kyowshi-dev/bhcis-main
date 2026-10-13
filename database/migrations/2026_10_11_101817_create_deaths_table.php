<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('deaths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->date('date_of_death');
            $table->string('place_of_death');
            $table->foreignId('cause_diagnosis_id')->nullable()->constrained('diagnosis_lookup')->onDelete('set null');
            $table->text('cause_text')->nullable();
            $table->boolean('maternal_death')->default(false);
            $table->boolean('perinatal_death')->default(false);
            $table->boolean('neonatal_death')->default(false);
            $table->boolean('infant_death')->default(false);
            $table->boolean('under_five_death')->default(false);
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['patient_id', 'date_of_death']);
            $table->index('maternal_death');
            $table->index('perinatal_death');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deaths');
    }
};
