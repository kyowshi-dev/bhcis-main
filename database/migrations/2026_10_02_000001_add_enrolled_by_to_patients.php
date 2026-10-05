<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('patients') && ! Schema::hasColumn('patients', 'enrolled_by')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->foreignId('enrolled_by')
                    ->nullable()
                    ->after('household_id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('patients') && Schema::hasColumn('patients', 'enrolled_by')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->dropConstrainedForeignId('enrolled_by');
            });
        }
    }
};
