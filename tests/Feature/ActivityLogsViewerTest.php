<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class ActivityLogsViewerTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    private function admin(): User
    {
        $admin = $this->createUserWithPermissions(['users']);

        $this->actingAs($admin);

        return $admin;
    }

    private function makeLog(array $attributes = []): AuditLog
    {
        return AuditLog::query()->create(array_merge([
            'user_id' => null,
            'action' => 'created',
            'table_name' => 'patients',
            'record_id' => 42,
            'old_values' => null,
            'new_values' => ['first_name' => 'Juan'],
            'ip_address' => '127.0.0.1',
            'category' => 'records',
            'severity' => 'info',
            'created_at' => now(),
        ], $attributes));
    }

    public function test_index_requires_users_permission(): void
    {
        $bhw = $this->createUserWithPermissions(['patients']);

        $this->actingAs($bhw)
            ->get(route('activity-logs.index'))
            ->assertForbidden();
    }

    public function test_show_requires_users_permission(): void
    {
        $log = $this->makeLog();
        $bhw = $this->createUserWithPermissions(['patients']);

        $this->actingAs($bhw)
            ->get(route('activity-logs.show', $log))
            ->assertForbidden();
    }

    public function test_index_renders_for_admin(): void
    {
        $this->admin();
        $this->makeLog();

        $this->get(route('activity-logs.index'))
            ->assertOk()
            ->assertSee('Activity Logs')
            ->assertSee('Events today')
            ->assertSee('Data changes');
    }

    public function test_index_filters_by_category(): void
    {
        $this->admin();
        $this->makeLog(['action' => 'created', 'table_name' => 'patients', 'category' => 'records']);
        $this->makeLog(['action' => 'login', 'table_name' => 'auth', 'category' => 'auth', 'record_id' => null]);

        $response = $this->get(route('activity-logs.index', ['category' => 'auth']));

        $response->assertOk();
        $this->assertSame(1, $response->viewData('logs')->total());
        $this->assertSame('login', $response->viewData('logs')->first()->action);
    }

    public function test_index_filters_by_severity(): void
    {
        $this->admin();
        $this->makeLog(['action' => 'created', 'severity' => 'info']);
        $this->makeLog(['action' => 'login_failed', 'table_name' => 'auth', 'severity' => 'error', 'record_id' => null]);

        $response = $this->get(route('activity-logs.index', ['severity' => 'error']));

        $response->assertOk();
        $this->assertSame(1, $response->viewData('logs')->total());
        $this->assertSame('login_failed', $response->viewData('logs')->first()->action);
    }

    public function test_failures_only_still_filters_errors(): void
    {
        $this->admin();
        $this->makeLog(['action' => 'created', 'severity' => 'info']);
        $this->makeLog(['action' => 'backup_export_failed', 'table_name' => 'backups', 'category' => 'admin', 'severity' => 'error', 'record_id' => null]);

        $response = $this->get(route('activity-logs.index', ['failures_only' => 1]));

        $response->assertOk();
        $this->assertSame(1, $response->viewData('logs')->total());
        $this->assertSame('backup_export_failed', $response->viewData('logs')->first()->action);
    }

    public function test_per_page_from_validated_request_is_honored(): void
    {
        $this->admin();
        foreach (range(1, 12) as $i) {
            $this->makeLog(['record_id' => $i]);
        }

        $response = $this->get(route('activity-logs.index', ['per_page' => 25]));

        $response->assertOk();
        $this->assertSame(25, $response->viewData('logs')->perPage());
    }

    public function test_invalid_per_page_falls_back_to_default(): void
    {
        $this->admin();
        $this->makeLog();

        $response = $this->get(route('activity-logs.index', ['per_page' => 7]));

        $response->assertOk();
        $this->assertSame(15, $response->viewData('logs')->perPage());
    }

    public function test_show_renders_changed_field_diff(): void
    {
        $this->admin();

        $log = $this->makeLog([
            'action' => 'updated',
            'old_values' => ['first_name' => 'Juan'],
            'new_values' => ['first_name' => 'Pedro'],
        ]);

        $this->get(route('activity-logs.show', $log))
            ->assertOk()
            ->assertSee('Field changes')
            ->assertSee('first_name')
            ->assertSee('Juan')
            ->assertSee('Pedro')
            ->assertSee('1 changed');
    }

    public function test_show_includes_record_link_when_viewer_has_permission(): void
    {
        $admin = $this->createUserWithPermissions(['users', 'patients']);
        $this->actingAs($admin);

        $log = $this->makeLog(['table_name' => 'patients', 'record_id' => 42]);

        $this->get(route('activity-logs.show', $log))
            ->assertOk()
            ->assertSee(route('patients.show', 42), false);
    }

    public function test_show_hides_record_link_without_permission(): void
    {
        $this->admin(); // only 'users' permission

        $log = $this->makeLog(['table_name' => 'patients', 'record_id' => 42]);

        $this->get(route('activity-logs.show', $log))
            ->assertOk()
            ->assertDontSee(route('patients.show', 42), false);
    }
}
