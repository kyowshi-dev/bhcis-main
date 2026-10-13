<?php

namespace Tests\Feature;

use App\Models\ApplicationSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class GracefulErrorsToggleTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    private function settingsPayload(array $overrides = []): array
    {
        return array_merge([
            'session_timeout' => 30,
            'login_max_attempts' => 5,
            'lockout_duration_minutes' => 15,
            'password_min_length' => 8,
        ], $overrides);
    }

    // ============================================================
    // TOGGLE STORAGE
    // ============================================================

    public function test_graceful_errors_is_enabled_by_default(): void
    {
        ApplicationSetting::query()->where('key', 'graceful_errors')->delete();

        $this->assertTrue(ApplicationSetting::gracefulErrorsEnabled());
    }

    public function test_graceful_errors_toggle_can_be_disabled(): void
    {
        $user = $this->createUserWithPermissions(['users']);

        $this->actingAs($user)
            ->put(route('profile.settings.update'), $this->settingsPayload())
            ->assertRedirect(route('profile.settings'));

        $this->assertSame('0', ApplicationSetting::get('graceful_errors'));
        $this->assertFalse(ApplicationSetting::gracefulErrorsEnabled());
    }

    public function test_graceful_errors_toggle_can_be_enabled(): void
    {
        ApplicationSetting::set('graceful_errors', '0');
        $user = $this->createUserWithPermissions(['users']);

        $this->actingAs($user)
            ->put(route('profile.settings.update'), $this->settingsPayload(['graceful_errors' => true]))
            ->assertRedirect(route('profile.settings'));

        $this->assertSame('1', ApplicationSetting::get('graceful_errors'));
        $this->assertTrue(ApplicationSetting::gracefulErrorsEnabled());
    }

    public function test_session_settings_page_exposes_flag_to_javascript(): void
    {
        $user = $this->createUserWithPermissions(['users']);

        $this->actingAs($user)
            ->get(route('profile.settings'))
            ->assertOk()
            ->assertSee('name="graceful_errors"', false)
            ->assertSee('"gracefulErrors":true', false);

        ApplicationSetting::set('graceful_errors', '0');

        $this->actingAs($user)
            ->get(route('profile.settings'))
            ->assertOk()
            ->assertSee('"gracefulErrors":false', false);
    }

    // ============================================================
    // SERVER-SIDE RENDERING (HTML)
    // ============================================================

    public function test_404_renders_custom_error_page_when_enabled(): void
    {
        $this->get('/no-such-page')
            ->assertNotFound()
            ->assertViewIs('error-pages.404');
    }

    public function test_404_falls_back_to_default_error_page_when_disabled(): void
    {
        ApplicationSetting::set('graceful_errors', '0');

        $response = $this->get('/no-such-page');

        $response->assertNotFound();
        $response->assertDontSee('BHCIS Sta. Ana');
        $response->assertDontSee('may have been moved');
    }

    public function test_403_renders_custom_error_page_when_enabled(): void
    {
        $user = $this->createUserWithPermissions([]);

        $this->actingAs($user)
            ->get('/users')
            ->assertForbidden()
            ->assertViewIs('error-pages.403');
    }

    public function test_403_falls_back_to_default_error_page_when_disabled(): void
    {
        ApplicationSetting::set('graceful_errors', '0');
        $user = $this->createUserWithPermissions([]);

        $response = $this->actingAs($user)->get('/users');

        $response->assertForbidden();
        $response->assertDontSee('BHCIS Sta. Ana');
        $response->assertSee('Forbidden');
    }

    // ============================================================
    // SERVER-SIDE RENDERING (JSON)
    // ============================================================

    public function test_404_json_uses_custom_message_when_enabled(): void
    {
        $this->getJson('/no-such-page')
            ->assertNotFound()
            ->assertJson(['message' => 'The requested resource was not found.']);
    }

    public function test_404_json_falls_back_to_default_when_disabled(): void
    {
        ApplicationSetting::set('graceful_errors', '0');

        $response = $this->getJson('/no-such-page');

        $response->assertNotFound();
        $this->assertNotSame(
            'The requested resource was not found.',
            $response->json('message'),
        );
    }
}
