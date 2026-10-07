<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vaccines_lookup') && ! Schema::hasColumn('vaccines_lookup', 'group_label')) {
            Schema::table('vaccines_lookup', function (Blueprint $table) {
                $table->string('group_label', 100)->nullable()->after('group_key');
            });
        }

        DB::table('vaccines_lookup')
            ->where('group_key', 'HEPA_B')
            ->update(['group_label' => 'Hepa B Birth Dose']);
    }

    public function down(): void
    {
        if (Schema::hasTable('vaccines_lookup') && Schema::hasColumn('vaccines_lookup', 'group_label')) {
            Schema::table('vaccines_lookup', function (Blueprint $table) {
                $table->dropColumn('group_label');
            });
        }
    }
};
