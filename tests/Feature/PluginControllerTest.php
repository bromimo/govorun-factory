<?php

namespace Tests\Feature;

use App\Models\Plugin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PluginControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_admin_can_view_plugins_list(): void
    {
        $admin = User::factory()->admin()->create();
        Plugin::factory()->count(2)->create();

        $response = $this->actingAs($admin)->get('/plugins');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Plugins/Index', false)
            ->has('plugins', 2)
        );
    }

    public function test_non_admin_cannot_access_plugins_page(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)->get('/plugins')->assertForbidden();
    }

    public function test_admin_can_create_a_plugin(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/plugins', [
            'name' => 'send_email',
            'description' => 'Send email block',
            'block_schema' => ['to' => 'string', 'subject' => 'string', 'body' => 'string'],
            'vue_component' => 'SendEmailForm',
            'php_stub' => '        $this->sendEmail(\'{{ $params["to"] }}\');',
            'active' => true,
        ]);

        $response->assertRedirect('/plugins');
        $this->assertDatabaseHas('plugins', ['name' => 'send_email']);
    }

    public function test_admin_can_toggle_plugin_active_state(): void
    {
        $admin = User::factory()->admin()->create();
        $plugin = Plugin::factory()->create(['active' => true]);

        $response = $this->actingAs($admin)->put("/plugins/{$plugin->id}", [
            'active' => false,
        ]);

        $response->assertRedirect('/plugins');
        $this->assertFalse($plugin->fresh()->active);
    }

    public function test_admin_can_delete_a_plugin(): void
    {
        $admin = User::factory()->admin()->create();
        $plugin = Plugin::factory()->create();

        $response = $this->actingAs($admin)->delete("/plugins/{$plugin->id}");

        $response->assertRedirect('/plugins');
        $this->assertDatabaseMissing('plugins', ['id' => $plugin->id]);
    }
}
