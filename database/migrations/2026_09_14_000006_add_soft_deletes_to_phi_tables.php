<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'patients',
            'households',
            'consultations',
            'vitals',
            'diagnosis_records',
            'prescriptions',
            'pregnancies',
            'prenatal_visits',
            'postnatal_records',
            'family_planning_clients',
            'family_planning_visits',
            'outward_referrals',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'patients',
            'households',
            'consultations',
            'vitals',
            'diagnosis_records',
            'prescriptions',
            'pregnancies',
            'prenatal_visits',
            'postnatal_records',
            'family_planning_clients',
            'family_planning_visits',
            'outward_referrals',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropSoftDeletes();
                });
            }
        }
    }
};
