<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->index('status', 'consultations_status_index');
            $table->index('created_at', 'consultations_created_at_index');
            $table->index('updated_at', 'consultations_updated_at_index');
            $table->index('notified_at', 'consultations_notified_at_index');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index('created_at', 'audit_logs_created_at_index');
        });

        Schema::table('immunization_records', function (Blueprint $table) {
            $table->index(['patient_id', 'vaccine_id', 'no_show'], 'immunization_records_patient_vaccine_noshow_index');
            $table->index(['date_given', 'no_show'], 'immunization_records_date_noshow_index');
        });

        Schema::table('pregnancies', function (Blueprint $table) {
            $table->index(['status', 'edc'], 'pregnancies_status_edc_index');
            $table->index('created_at', 'pregnancies_created_at_index');
        });

        Schema::table('prenatal_visits', function (Blueprint $table) {
            $table->index('next_visit_date', 'prenatal_visits_next_visit_date_index');
            $table->index(['pregnancy_id', 'visit_date'], 'prenatal_visits_pregnancy_visit_date_index');
            $table->index('visit_date', 'prenatal_visits_visit_date_index');
        });

        Schema::table('family_planning_clients', function (Blueprint $table) {
            $table->index(['is_active', 'schedule_next_visit'], 'fp_clients_active_schedule_index');
            $table->index('created_at', 'fp_clients_created_at_index');
        });

        Schema::table('postnatal_records', function (Blueprint $table) {
            $table->index('delivery_date', 'postnatal_records_delivery_date_index');
        });

        // FullText indexes (MySQL/MariaDB only)
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE diagnosis_lookup ADD FULLTEXT(diagnosis_name, diagnosis_code)');
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE diagnosis_lookup DROP INDEX diagnosis_name');
        }

        Schema::table('postnatal_records', function (Blueprint $table) {
            $table->dropIndex('postnatal_records_delivery_date_index');
        });

        Schema::table('family_planning_clients', function (Blueprint $table) {
            $table->dropIndex('fp_clients_active_schedule_index');
            $table->dropIndex('fp_clients_created_at_index');
        });

        Schema::table('prenatal_visits', function (Blueprint $table) {
            $table->dropIndex('prenatal_visits_next_visit_date_index');
            $table->dropIndex('prenatal_visits_pregnancy_visit_date_index');
            $table->dropIndex('prenatal_visits_visit_date_index');
        });

        Schema::table('pregnancies', function (Blueprint $table) {
            $table->dropIndex('pregnancies_status_edc_index');
            $table->dropIndex('pregnancies_created_at_index');
        });

        Schema::table('immunization_records', function (Blueprint $table) {
            $table->dropIndex('immunization_records_patient_vaccine_noshow_index');
            $table->dropIndex('immunization_records_date_noshow_index');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_created_at_index');
        });

        Schema::table('consultations', function (Blueprint $table) {
            $table->dropIndex('consultations_status_index');
            $table->dropIndex('consultations_created_at_index');
            $table->dropIndex('consultations_updated_at_index');
            $table->dropIndex('consultations_notified_at_index');
        });
    }
};
