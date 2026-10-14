<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RoleManagementController extends Controller
{
    public function __construct(
        private readonly AuditLogService $audit,
    ) {}

    public function index(): View
    {
        $this->authorizePermission('users');

        $roles = Role::with('permissions')->withCount('users')->orderBy('role_name')->get();

        return view('roles.index', [
            'roles' => $roles,
        ]);
    }

    public function edit(Role $role): View
    {
        $this->authorizePermission('users');

        return view('roles.edit', [
            'role' => $role->load('permissions'),
            'permissions' => Permission::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->authorizePermission('users');

        $validated = $request->validate([
            'role_name' => ['required', 'string', 'max:255', 'unique:user_roles,role_name,'.$role->id],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $submittedPermissionIds = array_map('intval', $validated['permissions'] ?? []);
        $userManagementId = (int) Permission::where('name', 'users')->value('id');

        // Prevent removing the 'users' permission from the Admin role.
        if ($role->role_name === 'Admin' && ! in_array($userManagementId, $submittedPermissionIds, true)) {
            return redirect()
                ->route('roles.edit', $role)
                ->withErrors(['permissions' => 'The Admin role must keep the "User Management" permission.'])
                ->withInput();
        }

        $oldRoleName = $role->role_name;
        $oldPermissions = $role->permissions()->pluck('permissions.id')->map(fn ($id) => (int) $id)->values()->all();

        DB::transaction(function () use ($role, $validated) {
            $role->update(['role_name' => $validated['role_name']]);
            $role->permissions()->sync($validated['permissions'] ?? []);

            // Clear permission caches for every user holding this role.
            foreach ($role->users as $user) {
                Cache::forget("user_permissions_{$user->id}");
            }
        });

        $this->audit->log(
            'role_updated',
            'user_roles',
            $request,
            null,
            $role->id,
            [
                'role_name' => $oldRoleName,
                'permission_ids' => $oldPermissions,
            ],
            [
                'role_name' => $role->role_name,
                'permission_ids' => array_map('intval', $validated['permissions'] ?? []),
            ]
        );

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role updated successfully.');
    }
}
