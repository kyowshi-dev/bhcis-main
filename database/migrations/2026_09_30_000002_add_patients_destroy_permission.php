<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Roles allowed to archive, restore, and permanently delete patients.
     * BHW is intentionally excluded (least privilege).
     *
     * @var list<string>
     */
    private array $grantedRoles = ['Admin', 'Doctor', 'Nurse', 'Midwife'];

    /**
     * Registry permissions BHW must never hold.
     *
     * @var list<string>
     */
    private array $bhwDeniedPermissions = ['household', 'patients'];

    public function up(): void
    {
        $this->grantPatientsDestroy();

        // Repair pre-existing least-privilege drift: 000001 already stripped
        // household/patients from BHW, but something re-granted them on the
        // live DB, so BHW can still open the patient registry. Idempotent.
        $this->stripDeniedPermissionsFromBhw();
    }

    public function down(): void
    {
        // Only the new permission is undone here. Re-granting household/patients
        // to BHW is 000001's down() - rolling back 000002 alone must not
        // reintroduce registry access for BHW.
        $permissionId = DB::table('permissions')->where('name', 'patients_destroy')->value('id');

        if ($permissionId === null) {
            return;
        }

        $roleIds = DB::table('user_roles')
            ->whereIn('role_name', $this->grantedRoles)
            ->pluck('id')
            ->all();

        DB::table('role_permissions')
            ->whereIn('role_id', $roleIds)
            ->where('permission_id', $permissionId)
            ->delete();

        DB::table('permissions')->where('id', $permissionId)->delete();

        $this->clearPermissionCaches($roleIds);
    }

    private function grantPatientsDestroy(): void
    {
        DB::table('permissions')->insertOrIgnore([
            'name' => 'patients_destroy',
            'description' => 'Archive, restore, and permanently delete patient records',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $permissionId = DB::table('permissions')->where('name', 'patients_destroy')->value('id');

        if ($permissionId === null) {
            return;
        }

        $roleIds = DB::table('user_roles')
            ->whereIn('role_name', $this->grantedRoles)
            ->pluck('id')
            ->all();

        foreach ($roleIds as $roleId) {
            DB::table('role_permissions')->insertOrIgnore([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->clearPermissionCaches($roleIds);
    }

    private function stripDeniedPermissionsFromBhw(): void
    {
        $bhwRoleId = DB::table('user_roles')->where('role_name', 'BHW')->value('id');

        if ($bhwRoleId === null) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', $this->bhwDeniedPermissions)
            ->pluck('id')
            ->all();

        if (empty($permissionIds)) {
            return;
        }

        DB::table('role_permissions')
            ->where('role_id', $bhwRoleId)
            ->whereIn('permission_id', $permissionIds)
            ->delete();

        $this->clearPermissionCachesForRole($bhwRoleId);
    }

    /**
     * Permission lists are cached per user for 3600s; drop the cache for
     * every user holding one of the given roles.
     *
     * @param  list<int>  $roleIds
     */
    private function clearPermissionCaches(array $roleIds): void
    {
        foreach ($roleIds as $roleId) {
            $this->clearPermissionCachesForRole($roleId);
        }
    }

    private function clearPermissionCachesForRole(int $roleId): void
    {
        $userIds = DB::table('users')
            ->where('role_id', $roleId)
            ->pluck('id');

        foreach ($userIds as $userId) {
            Cache::forget("user_permissions_{$userId}");
        }
    }
};
