<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outward_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained('consultations')->cascadeOnDelete();
            $table->string('destination_facility');
            $table->text('pertinent_history');
            $table->text('actions_taken')->nullable();
            $table->text('specific_details')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->unique('consultation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outward_referrals');
    }
};
