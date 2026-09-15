<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Route;
use Throwable;

class BreadcrumbHelper
{
    private const DASHBOARD = 'dashboard';

    /**
     * Routes that belong under a category label (non-clickable) in the breadcrumb.
     * Used to insert "Maternal Care" between Dashboard and the sub-module index.
     */
    private const CATEGORY_PARENTS = [
        'maternal.prenatal.index' => 'Maternal Care',
        'maternal.prenatal.patient' => 'Maternal Care',
        'maternal.postnatal.index' => 'Maternal Care',
        'maternal.postnatal.patient' => 'Maternal Care',
        'maternal.family-planning.index' => 'Maternal Care',
        'maternal.family-planning.patient' => 'Maternal Care',
    ];

    /**
     * Route name -> page label for every HTML page in the app.
     * Anything not listed here (search, exports, prints, polls) is never tracked.
     */
    public const PAGE_LABELS = [
        'dashboard' => 'Dashboard',
        'households.index' => 'Households',
        'households.create' => 'Add Household',
        'households.edit' => 'Edit Household',
        'patients.index' => 'Patients',
        'patients.create' => 'Add Patient',
        'patients.show' => 'Patient Details',
        'consultations.index' => 'Consultations',
        'consultations.create' => 'New Consultation',
        'consultations.show' => 'Consultation Details',
        'consultations.edit' => 'Edit Consultation',
        'referrals.index' => 'Referrals',
        'immunizations.index' => 'Vaccinations',
        'immunizations.enroll-infant.create' => 'Enroll Infant',
        'immunizations.patient' => 'Vaccination Record',
        'maternal.prenatal.index' => 'Prenatal',
        'maternal.prenatal.patient' => 'Prenatal Details',
        'maternal.postnatal.index' => 'Postnatal',
        'maternal.postnatal.patient' => 'Postnatal Details',
        'maternal.family-planning.index' => 'Family Planning',
        'maternal.family-planning.patient' => 'Family Planning Details',
        'reports.index' => 'Reports',
        'reports.morbidity' => 'Morbidity Report',
        'reports.mch-epi-fp' => 'Maternal, EPI & Family Planning',
        'medicines.index' => 'Medicines',
        'medicines.create' => 'Add Medicine',
        'medicines.show' => 'Medicine Details',
        'medicines.edit' => 'Edit Medicine',
        'zones.index' => 'Zones',
        'zones.create' => 'Add Zone',
        'zones.show' => 'Zone Details',
        'zones.edit' => 'Edit Zone',
        'users.index' => 'User Management',
        'users.create' => 'Add User',
        'users.edit' => 'Edit User',
        'roles.index' => 'Roles',
        'roles.edit' => 'Edit Role',
        'activity-logs.index' => 'Activity Logs',
        'activity-logs.show' => 'Log Detail',
        'settings.index' => 'Settings',
        'settings.account' => 'Account Settings',
        'settings.backups' => 'Backups',
        'profile.show' => 'My Profile',
        'profile.edit' => 'Edit Profile',
        'profile.settings' => 'Session Settings',
        'notifications.index' => 'Notifications',
        'privacy.index' => 'Privacy Settings',
        'privacy.policy' => 'Privacy Policy',
        'privacy.purposes' => 'Data Processing Purposes',
    ];

    /**
     * Static hierarchy: child route => parent route.
     * The chain is always rooted at Dashboard.
     */
    private const PARENTS = [
        'households.create' => 'households.index',
        'households.edit' => 'households.index',
        'patients.create' => 'patients.index',
        'patients.show' => 'patients.index',
        'consultations.create' => 'patients.show',
        'consultations.show' => 'consultations.index',
        'consultations.edit' => 'consultations.show',
        'immunizations.enroll-infant.create' => 'immunizations.index',
        'immunizations.patient' => 'immunizations.index',
        'maternal.prenatal.patient' => 'maternal.prenatal.index',
        'maternal.postnatal.patient' => 'maternal.postnatal.index',
        'maternal.family-planning.patient' => 'maternal.family-planning.index',
        'reports.morbidity' => 'reports.index',
        'reports.mch-epi-fp' => 'reports.index',
        'medicines.create' => 'medicines.index',
        'medicines.show' => 'medicines.index',
        'medicines.edit' => 'medicines.show',
        'zones.create' => 'zones.index',
        'zones.show' => 'zones.index',
        'zones.edit' => 'zones.show',
        'users.create' => 'users.index',
        'users.edit' => 'users.index',
        'roles.edit' => 'roles.index',
        'activity-logs.show' => 'activity-logs.index',
        'settings.account' => 'settings.index',
        'settings.backups' => 'settings.index',
        'privacy.index' => 'settings.index',
        'privacy.policy' => 'settings.index',
        'privacy.purposes' => 'settings.index',
        'profile.edit' => 'profile.show',
        'profile.settings' => 'profile.show',
    ];

    public static function isPageRoute(?string $routeName): bool
    {
        return $routeName !== null && isset(self::PAGE_LABELS[$routeName]);
    }

    /**
     * Build the breadcrumb chain for the current route by walking up the
     * static parent hierarchy. No session state is used.
     *
     * @return array<int, array{name: string, url: string|null}>
     */
    public static function getBreadcrumbs(): array
    {
        $routeName = Route::currentRouteName();

        if (! self::isPageRoute($routeName)) {
            return [];
        }

        $chain = [];
        $current = $routeName;

        // Walk up the parent hierarchy to Dashboard.
        while ($current !== null && $current !== self::DASHBOARD) {
            $chain[] = self::crumb($current);
            $current = self::PARENTS[$current] ?? null;
        }

        // Insert non-clickable category label if this route belongs to a category group.
        if (isset(self::CATEGORY_PARENTS[$routeName])) {
            $chain[] = ['name' => self::CATEGORY_PARENTS[$routeName], 'url' => null];
        }

        $chain[] = self::crumb(self::DASHBOARD);

        return array_reverse($chain);
    }

    /**
     * @return array{name: string, url: string|null}
     */
    private static function crumb(string $routeName, ?string $url = null): array
    {
        if ($url === null) {
            try {
                $url = route($routeName);
            } catch (Throwable) {
                $url = null;
            }
        }

        return [
            'name' => self::PAGE_LABELS[$routeName],
            'url' => $url,
        ];
    }
}
