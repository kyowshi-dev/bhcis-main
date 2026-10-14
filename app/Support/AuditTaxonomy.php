<?php

namespace App\Support;

use App\Models\AuditLog;

/**
 * Central taxonomy for audit log actions.
 * Maps action + table_name -> category, severity, human label, icon.
 */
final class AuditTaxonomy
{
    public const CATEGORY_AUTH = 'auth';

    public const CATEGORY_RECORDS = 'records';

    public const CATEGORY_ADMIN = 'admin';

    public const SEVERITY_INFO = 'info';

    public const SEVERITY_WARNING = 'warning';

    public const SEVERITY_ERROR = 'error';

    /** @var array<string, array{category: string, severity: string, label: string, icon: string}> */
    private static array $actionMap = [
        // Authentication
        'login' => ['category' => self::CATEGORY_AUTH, 'severity' => self::SEVERITY_INFO, 'label' => 'Sign in', 'icon' => 'fa-solid fa-right-to-bracket'],
        'logout' => ['category' => self::CATEGORY_AUTH, 'severity' => self::SEVERITY_INFO, 'label' => 'Sign out', 'icon' => 'fa-solid fa-right-from-bracket'],
        'login_failed' => ['category' => self::CATEGORY_AUTH, 'severity' => self::SEVERITY_ERROR, 'label' => 'Failed sign-in', 'icon' => 'fa-solid fa-user-lock'],
        'login_blocked_locked' => ['category' => self::CATEGORY_AUTH, 'severity' => self::SEVERITY_ERROR, 'label' => 'Blocked (account locked)', 'icon' => 'fa-solid fa-lock'],
        'login_blocked_deactivated' => ['category' => self::CATEGORY_AUTH, 'severity' => self::SEVERITY_ERROR, 'label' => 'Blocked (deactivated)', 'icon' => 'fa-solid fa-user-slash'],
        'password_reset' => ['category' => self::CATEGORY_AUTH, 'severity' => self::SEVERITY_INFO, 'label' => 'Password reset', 'icon' => 'fa-solid fa-key'],

        // Administration
        'settings_updated' => ['category' => self::CATEGORY_ADMIN, 'severity' => self::SEVERITY_INFO, 'label' => 'Settings updated', 'icon' => 'fa-solid fa-gear'],
        'role_updated' => ['category' => self::CATEGORY_ADMIN, 'severity' => self::SEVERITY_WARNING, 'label' => 'Role updated', 'icon' => 'fa-solid fa-user-shield'],
        'backup_exported' => ['category' => self::CATEGORY_ADMIN, 'severity' => self::SEVERITY_INFO, 'label' => 'Backup exported', 'icon' => 'fa-solid fa-file-export'],
        'backup_export_failed' => ['category' => self::CATEGORY_ADMIN, 'severity' => self::SEVERITY_ERROR, 'label' => 'Backup export failed', 'icon' => 'fa-solid fa-triangle-exclamation'],
        'backup_imported' => ['category' => self::CATEGORY_ADMIN, 'severity' => self::SEVERITY_INFO, 'label' => 'Backup imported', 'icon' => 'fa-solid fa-file-import'],
        'backup_import_failed' => ['category' => self::CATEGORY_ADMIN, 'severity' => self::SEVERITY_ERROR, 'label' => 'Backup import failed', 'icon' => 'fa-solid fa-triangle-exclamation'],
        'medicines_imported' => ['category' => self::CATEGORY_ADMIN, 'severity' => self::SEVERITY_INFO, 'label' => 'Medicines imported', 'icon' => 'fa-solid fa-pills'],
        'medicines_import_failed' => ['category' => self::CATEGORY_ADMIN, 'severity' => self::SEVERITY_ERROR, 'label' => 'Medicines import failed', 'icon' => 'fa-solid fa-triangle-exclamation'],

        // Records (trait - generic CRUD, overridden per-table below where needed)
        'created' => ['category' => self::CATEGORY_RECORDS, 'severity' => self::SEVERITY_INFO, 'label' => 'Created', 'icon' => 'fa-solid fa-plus'],
        'updated' => ['category' => self::CATEGORY_RECORDS, 'severity' => self::SEVERITY_INFO, 'label' => 'Updated', 'icon' => 'fa-solid fa-pen'],
        'deleted' => ['category' => self::CATEGORY_RECORDS, 'severity' => self::SEVERITY_WARNING, 'label' => 'Deleted', 'icon' => 'fa-solid fa-trash'],
    ];

    /** Tables that map to admin category for generic CRUD */
    private static array $adminTables = [
        'users' => true,
        'user_roles' => true,
        'application_settings' => true,
        'settings' => true,
        'medicines_lookup' => true,
    ];

    /**
     * Record link map: table_name -> ['route', 'permission', 'param'].
     * 'permission' may be a string or an array (any-of), matching route middleware.
     */
    private static array $recordLinkMap = [
        'patients' => ['route' => 'patients.show', 'permission' => ['patients', 'consultations']],
        'deaths' => ['route' => 'deaths.show', 'permission' => 'patients'],
        'users' => ['route' => 'users.edit', 'permission' => 'users'],
        'consultations' => ['route' => 'consultations.show', 'permission' => 'consultations'],
        'households' => ['route' => 'households.show', 'permission' => 'household'],
        'zones' => ['route' => 'zones.show', 'permission' => 'zones'],
        'medicines_lookup' => ['route' => 'medicines.show', 'permission' => 'medicines'],
    ];

    /** Table display name map */
    private static array $tableLabels = [
        'patients' => 'Patient',
        'deaths' => 'Death record',
        'users' => 'User',
        'consultations' => 'Consultation',
        'households' => 'Household',
        'household_members' => 'Household member',
        'zones' => 'Zone',
        'medicines_lookup' => 'Medicine',
        'application_settings' => 'Setting',
        'settings' => 'Setting',
        'user_roles' => 'Role',
        'immunizations' => 'Immunization',
        'maternal_profiles' => 'Maternal profile',
        'prenatal_visits' => 'Prenatal visit',
        'postnatal_records' => 'Postnatal record',
        'pregnancies' => 'Pregnancy',
        'family_planning_clients' => 'Family planning client',
        'family_planning_visits' => 'Family planning visit',
        'diagnosis_lookup' => 'Diagnosis',
        'diagnosis_records' => 'Diagnosis record',
        'prescriptions' => 'Prescription',
        'vitals' => 'Vitals',
        'health_workers' => 'Health worker',
        'outward_referrals' => 'Referral',
        'backups' => 'Backup',
        'auth' => 'Authentication',
    ];

    /**
     * Classify an action for a given table.
     *
     * @return array{category: string, severity: string, label: string, icon: string}
     */
    public static function classify(string $action, string $table): array
    {
        // Generic CRUD actions: category depends on the table
        // (checked before the explicit map so admin tables like users classify correctly)
        if (in_array($action, ['created', 'updated', 'deleted'], true)) {
            $category = isset(self::$adminTables[$table]) ? self::CATEGORY_ADMIN : self::CATEGORY_RECORDS;
            $severity = $action === 'deleted' ? self::SEVERITY_WARNING : self::SEVERITY_INFO;

            return [
                'category' => $category,
                'severity' => $severity,
                'label' => ucfirst($action),
                'icon' => self::$actionMap[$action]['icon'] ?? 'fa-solid fa-circle',
            ];
        }

        // Explicit action map takes precedence for semantic actions
        if (isset(self::$actionMap[$action])) {
            return self::$actionMap[$action];
        }

        // Fallback
        return [
            'category' => self::CATEGORY_RECORDS,
            'severity' => self::SEVERITY_INFO,
            'label' => ucfirst(str_replace('_', ' ', $action)),
            'icon' => 'fa-solid fa-circle',
        ];
    }

    /** Get human-readable table label */
    public static function tableLabel(string $table): string
    {
        return self::$tableLabels[$table] ?? ucfirst(str_replace('_', ' ', $table));
    }

    /** Get record link info if available */
    public static function recordLink(string $table): ?array
    {
        return self::$recordLinkMap[$table] ?? null;
    }

    /** Get all distinct categories */
    public static function categories(): array
    {
        return [self::CATEGORY_AUTH, self::CATEGORY_RECORDS, self::CATEGORY_ADMIN];
    }

    /** Get all distinct severities */
    public static function severities(): array
    {
        return [self::SEVERITY_INFO, self::SEVERITY_WARNING, self::SEVERITY_ERROR];
    }

    /** Category label for UI */
    public static function categoryLabel(string $category): string
    {
        return match ($category) {
            self::CATEGORY_AUTH => 'Sign-ins',
            self::CATEGORY_RECORDS => 'Data changes',
            self::CATEGORY_ADMIN => 'Administration',
            default => ucfirst($category),
        };
    }

    /** Severity label for UI */
    public static function severityLabel(string $severity): string
    {
        return match ($severity) {
            self::SEVERITY_INFO => 'Info',
            self::SEVERITY_WARNING => 'Warning',
            self::SEVERITY_ERROR => 'Error',
            default => ucfirst($severity),
        };
    }

    /**
     * Build the event part of a human-readable sentence (without the actor).
     * Callers prefix the username: e.g. "Alice created Patient #12".
     * Used by the AuditLog description accessor and the dashboard feed (raw DB rows).
     */
    public static function describeEvent(string $action, string $table, ?int $recordId = null): string
    {
        $taxonomy = self::classify($action, $table);

        if (in_array($action, ['created', 'updated', 'deleted'], true)) {
            return strtolower($taxonomy['label']).' '.self::tableLabel($table).($recordId !== null ? " #{$recordId}" : '');
        }

        // Semantic events (auth/admin): the action label already reads as a phrase
        return $taxonomy['label'].($recordId !== null && $table !== 'auth' ? ' ('.self::tableLabel($table)." #{$recordId})" : '');
    }

    /** Badge classes for a given severity/category combo (for blade) */
    public static function badgeClasses(string $severity, string $category): string
    {
        return match ($severity) {
            self::SEVERITY_ERROR => 'bg-danger-soft text-danger border-danger/30',
            self::SEVERITY_WARNING => 'bg-amber-soft text-amber border-amber/30',
            default => match ($category) {
                self::CATEGORY_AUTH => 'bg-accent-blue-soft text-accent-blue border-accent-blue/30',
                self::CATEGORY_ADMIN => 'bg-primary-soft text-primary border-primary/30',
                default => 'bg-teal-soft text-primary border-primary/30',
            },
        };
    }

    /** Icon for a given action (fallback to category icon) */
    public static function actionIcon(string $action): string
    {
        return self::$actionMap[$action]['icon'] ?? 'fa-solid fa-circle';
    }
}
