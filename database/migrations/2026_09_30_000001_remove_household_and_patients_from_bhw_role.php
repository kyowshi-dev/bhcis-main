<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $bhwRoleId = DB::table('user_roles')->where('role_name', 'BHW')->value('id');

        if ($bhwRoleId === null) {
            return;
        }

        $permIds = DB::table('permissions')
            ->whereIn('name', ['household', 'patients'])
            ->pluck('id')
            ->all();

        if (empty($permIds)) {
            return;
        }

        DB::table('role_permissions')
            ->where('role_id', $bhwRoleId)
            ->whereIn('permission_id', $permIds)
            ->delete();

        $userIds = DB::table('users')
            ->where('role_id', $bhwRoleId)
            ->pluck('id')
            ->all();

        foreach ($userIds as $uid) {
            Cache::forget("user_permissions_{$uid}");
        }
    }

    public function down(): void
    {
        $bhwRoleId = DB::table('user_roles')->where('role_name', 'BHW')->value('id');

        if ($bhwRoleId === null) {
            return;
        }

        $permIds = DB::table('permissions')
            ->whereIn('name', ['household', 'patients'])
            ->pluck('id')
            ->all();

        if (empty($permIds)) {
            return;
        }

        foreach ($permIds as $permId) {
            DB::table('role_permissions')->insertOrIgnore([
                'role_id' => $bhwRoleId,
                'permission_id' => $permId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $userIds = DB::table('users')
            ->where('role_id', $bhwRoleId)
            ->pluck('id')
            ->all();

        foreach ($userIds as $uid) {
            Cache::forget("user_permissions_{$uid}");
        }
    }
};
