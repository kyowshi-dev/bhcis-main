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
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('category', 20)->default('records')->after('table_name');
            $table->string('severity', 10)->default('info')->after('category');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index('category', 'audit_logs_category_index');
            $table->index('severity', 'audit_logs_severity_index');
        });

        // Backfill existing rows using the same classification logic
        $this->backfillTaxonomy();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_category_index');
            $table->dropIndex('audit_logs_severity_index');
            $table->dropColumn(['category', 'severity']);
        });
    }

    private function backfillTaxonomy(): void
    {
        // Snapshot of the classification logic at migration time
        // This avoids coupling to the app class which may change
        $actionMap = [
            'login' => ['category' => 'auth', 'severity' => 'info'],
            'logout' => ['category' => 'auth', 'severity' => 'info'],
            'login_failed' => ['category' => 'auth', 'severity' => 'error'],
            'login_blocked_locked' => ['category' => 'auth', 'severity' => 'error'],
            'login_blocked_deactivated' => ['category' => 'auth', 'severity' => 'error'],
            'password_reset' => ['category' => 'auth', 'severity' => 'info'],
            'settings_updated' => ['category' => 'admin', 'severity' => 'info'],
            'role_updated' => ['category' => 'admin', 'severity' => 'warning'],
            'backup_exported' => ['category' => 'admin', 'severity' => 'info'],
            'backup_export_failed' => ['category' => 'admin', 'severity' => 'error'],
            'backup_imported' => ['category' => 'admin', 'severity' => 'info'],
            'backup_import_failed' => ['category' => 'admin', 'severity' => 'error'],
            'medicines_imported' => ['category' => 'admin', 'severity' => 'info'],
            'medicines_import_failed' => ['category' => 'admin', 'severity' => 'error'],
            'death_created' => ['category' => 'records', 'severity' => 'info'],
            'death_updated' => ['category' => 'records', 'severity' => 'info'],
            'death_deleted' => ['category' => 'records', 'severity' => 'warning'],
        ];

        $adminTables = [
            'users' => true,
            'user_roles' => true,
            'application_settings' => true,
            'settings' => true,
            'medicines_lookup' => true,
        ];

        $logs = DB::table('audit_logs')->get(['id', 'action', 'table_name']);

        foreach ($logs as $log) {
            $action = $log->action;
            $table = $log->table_name;

            if (isset($actionMap[$action])) {
                DB::table('audit_logs')
                    ->where('id', $log->id)
                    ->update([
                        'category' => $actionMap[$action]['category'],
                        'severity' => $actionMap[$action]['severity'],
                    ]);

                continue;
            }

            // Generic trait actions
            if (in_array($action, ['created', 'updated', 'deleted'], true)) {
                $category = isset($adminTables[$table]) ? 'admin' : 'records';
                $severity = $action === 'deleted' ? 'warning' : 'info';

                DB::table('audit_logs')
                    ->where('id', $log->id)
                    ->update([
                        'category' => $category,
                        'severity' => $severity,
                    ]);
            }
        }
    }
};
