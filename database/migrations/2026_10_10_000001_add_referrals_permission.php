<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Roles that keep referral tracking access. Seeded from roles that
     * currently hold the consultations permission so the split does not
     * change effective access on existing installs.
     *
     * @var list<string>
     */
    private array $defaultGrantedRoles = ['Admin', 'Doctor', 'Nurse', 'Midwife', 'BHW'];

    public function up(): void
    {
        DB::table('permissions')->insertOrIgnore([
            'name' => 'referrals',
            'description' => 'Access to Referrals module (track outward referrals and update their status)',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $permissionId = DB::table('permissions')->where('name', 'referrals')->value('id');

        if ($permissionId === null) {
            return;
        }

        // Grant to every role that could previously reach /referrals via the
        // consultations permission, plus the known default role set.
        $roleIds = DB::table('user_roles')
            ->whereIn('role_name', $this->defaultGrantedRoles)
            ->orWhereIn('id', function ($query) {
                $query->select('role_id')
                    ->from('role_permissions')
                    ->whereIn('permission_id', function ($sub) {
                        $sub->select('id')->from('permissions')->where('name', 'consultations');
                    });
            })
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

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('name', 'referrals')->value('id');

        if ($permissionId === null) {
            return;
        }

        $roleIds = DB::table('role_permissions')
            ->where('permission_id', $permissionId)
            ->pluck('role_id')
            ->all();

        DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
        DB::table('permissions')->where('id', $permissionId)->delete();

        $this->clearPermissionCaches($roleIds);
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
            $userIds = DB::table('users')
                ->where('role_id', $roleId)
                ->pluck('id');

            foreach ($userIds as $userId) {
                Cache::forget("user_permissions_{$userId}");
            }
        }
    }
};
