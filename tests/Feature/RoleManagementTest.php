<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    public function test_edit_form_submits_locked_user_management_permission_for_admin_role(): void
    {
        $this->actingAs($this->createAdminUser());

        $submitted = $this->submittedPermissionIds($this->adminRoleId());

        $this->assertContains($this->permissionId('users'), $submitted);
    }

    public function test_updating_admin_role_to_hide_another_module_succeeds(): void
    {
        $this->actingAs($this->createAdminUser());

        $roleId = $this->adminRoleId();
        $patientsId = $this->permissionId('patients');

        $permissions = array_map(
            'strval',
            array_values(array_diff($this->submittedPermissionIds($roleId), [$patientsId]))
        );

        $this->put(route('roles.update', $roleId), [
            'role_name' => 'Admin',
            'permissions' => $permissions,
        ])->assertRedirect(route('roles.index'));

        $attached = $this->attachedPermissionIds($roleId);

        $this->assertContains($this->permissionId('users'), $attached);
        $this->assertNotContains($patientsId, $attached);
    }

    public function test_admin_role_update_omitting_user_management_is_rejected(): void
    {
        $this->actingAs($this->createAdminUser());

        $roleId = $this->adminRoleId();

        $this->put(route('roles.update', $roleId), [
            'role_name' => 'Admin',
            'permissions' => [(string) $this->permissionId('patients')],
        ])
            ->assertRedirect(route('roles.edit', $roleId))
            ->assertSessionHasErrors('permissions');

        $attached = $this->attachedPermissionIds($roleId);

        $this->assertContains($this->permissionId('users'), $attached);
        $this->assertContains($this->permissionId('patients'), $attached);
    }

    public function test_permission_selections_survive_a_validation_error(): void
    {
        $this->actingAs($this->createAdminUser());

        $roleId = $this->adminRoleId();

        $this->get(route('roles.edit', $roleId))->assertOk();

        $permissions = array_map(
            'strval',
            $this->submittedPermissionIds($roleId)
        );

        $this->put(route('roles.update', $roleId), [
            'role_name' => 'Nurse',
            'permissions' => $permissions,
        ])->assertSessionHasErrors('role_name');

        $html = $this->get(route('roles.edit', $roleId))->assertOk()->getContent();

        $patientsTag = $this->permissionInputTag($html, $this->permissionId('patients'));

        $this->assertNotNull($patientsTag, 'Patients permission checkbox is missing from the edit form.');
        $this->assertMatchesRegularExpression('/\schecked(?:\s|>)/', $patientsTag);
    }

    private function createAdminUser(): User
    {
        return $this->createUserWithNamedRole('Admin', ['users', 'patients']);
    }

    private function adminRoleId(): int
    {
        return (int) DB::table('user_roles')->where('role_name', 'Admin')->value('id');
    }

    private function permissionId(string $name): int
    {
        return (int) DB::table('permissions')->where('name', $name)->value('id');
    }

    /**
     * @return list<int>
     */
    private function attachedPermissionIds(int $roleId): array
    {
        return DB::table('role_permissions')
            ->where('role_id', $roleId)
            ->pluck('permission_id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Permission ids the browser would submit from the rendered edit form
     * (disabled inputs are not submitted).
     *
     * @return list<int>
     */
    private function submittedPermissionIds(int $roleId): array
    {
        $html = $this->get(route('roles.edit', $roleId))->assertOk()->getContent();

        $ids = [];

        foreach ($this->permissionInputTags($html) as $tag) {
            if (preg_match('/\sdisabled(?:\s|>)/', $tag)) {
                continue;
            }

            if (preg_match('/value="(\d+)"/', $tag, $matches)) {
                $ids[] = (int) $matches[1];
            }
        }

        return $ids;
    }

    /**
     * @return list<string>
     */
    private function permissionInputTags(string $html): array
    {
        preg_match_all('/<input\b[^>]*>/', $html, $matches);

        return array_values(array_filter(
            $matches[0],
            fn (string $tag): bool => str_contains($tag, 'name="permissions[]"')
        ));
    }

    private function permissionInputTag(string $html, int $permissionId): ?string
    {
        foreach ($this->permissionInputTags($html) as $tag) {
            if (preg_match('/value="(\d+)"/', $tag, $matches) && (int) $matches[1] === $permissionId) {
                return $tag;
            }
        }

        return null;
    }
}
