<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('psgc_code', 12)->unique();
            $table->string('name')->unique();
        });

        Schema::create('municipalities', function (Blueprint $table) {
            $table->id();
            $table->string('psgc_code', 12)->unique();
            $table->foreignId('province_id')->constrained('provinces')->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_capital')->default(false);
            $table->unique(['province_id', 'name']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipalities');
        Schema::dropIfExists('provinces');
    }
};
