<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'role:admin'])->get('/test-admin-only', fn () => 'OK');
        Route::middleware(['web', 'auth', 'role:admin,editor'])->get('/test-admin-editor', fn () => 'OK');
    }

    public function test_admin_passes_role_admin_middleware(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/test-admin-only')->assertOk();
    }

    public function test_editor_is_blocked_by_role_admin_middleware(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)->get('/test-admin-only')->assertForbidden();
    }

    public function test_viewer_is_blocked_by_role_admin_middleware(): void
    {
        $viewer = User::factory()->create();

        $this->actingAs($viewer)->get('/test-admin-only')->assertForbidden();
    }

    public function test_editor_passes_role_admin_editor_middleware(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)->get('/test-admin-editor')->assertOk();
    }

    public function test_viewer_is_blocked_by_role_admin_editor_middleware(): void
    {
        $viewer = User::factory()->create();

        $this->actingAs($viewer)->get('/test-admin-editor')->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/test-admin-only')->assertRedirect('/login');
    }
}
