<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('households', function (Blueprint $table) {
            if (Schema::hasColumn('households', 'family_name_head')) {
                $table->renameColumn('family_name_head', 'head_last_name');
            }

            if (! Schema::hasColumn('households', 'head_first_name')) {
                $table->string('head_first_name')->nullable()->after('zone_id');
            }

            if (! Schema::hasColumn('households', 'head_middle_name')) {
                $table->string('head_middle_name')->nullable()->after('head_first_name');
            }

            if (! Schema::hasColumn('households', 'head_suffix')) {
                $table->string('head_suffix')->nullable()->after('head_last_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('households', function (Blueprint $table) {
            if (Schema::hasColumn('households', 'head_suffix')) {
                $table->dropColumn('head_suffix');
            }

            if (Schema::hasColumn('households', 'head_middle_name')) {
                $table->dropColumn('head_middle_name');
            }

            if (Schema::hasColumn('households', 'head_first_name')) {
                $table->dropColumn('head_first_name');
            }

            if (Schema::hasColumn('households', 'head_last_name')) {
                $table->renameColumn('head_last_name', 'family_name_head');
            }
        });
    }
};
