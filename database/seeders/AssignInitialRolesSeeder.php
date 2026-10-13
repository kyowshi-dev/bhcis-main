<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AssignInitialRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Assigns permissions to roles (not to individual users).
     */
    public function run(): void
    {
        $rolePermissionMap = [
            // ADMIN
            'Admin' => Permission::query()->pluck('name')->toArray(),

            // DOCTOR
            'Doctor' => ['patients', 'patients_destroy', 'consultations', 'referrals', 'medicines', 'maternal', 'print_handouts', 'dashboard_handouts'],

            // NURSE
            'Nurse' => ['patients', 'patients_destroy', 'consultations', 'referrals', 'medicines', 'immunizations', 'maternal', 'print_handouts', 'dashboard_handouts', 'nurse_batch_entry'],

            // MIDWIFE
            'Midwife' => ['patients', 'patients_destroy', 'consultations', 'referrals', 'immunizations', 'maternal', 'reports', 'print_handouts', 'dashboard_handouts', 'nurse_batch_entry'],

            // BHW
            'BHW' => ['consultations', 'referrals', 'immunizations', 'maternal', 'reports', 'print_handouts', 'dashboard_handouts'],
        ];

        foreach ($rolePermissionMap as $roleName => $permissionNames) {
            $role = Role::query()->where('role_name', $roleName)->first();

            if ($role === null) {
                continue;
            }

            $permissionIds = Permission::query()
                ->whereIn('name', $permissionNames)
                ->pluck('id')
                ->all();

            $role->permissions()->sync($permissionIds);
        }
    }
}
