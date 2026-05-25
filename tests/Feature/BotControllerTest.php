<?php

namespace Tests\Feature;

use App\Models\Bot;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BotControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_all_bots_to_any_authenticated_user(): void
    {
        $admin = User::factory()->admin()->create();
        Bot::factory()->count(3)->for($admin, 'creator')->create();

        $response = $this->actingAs($admin)->get('/');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard/Index')
            ->has('bots', 3)
        );
    }

    public function test_dashboard_search_filters_bots_by_name(): void
    {
        $admin = User::factory()->admin()->create();
        Bot::factory()->for($admin, 'creator')->create(['name' => 'Sales Bot']);
        Bot::factory()->for($admin, 'creator')->create(['name' => 'Support Bot']);

        $response = $this->actingAs($admin)->get('/?search=Sales');

        $response->assertInertia(fn ($page) => $page->has('bots', 1));
    }

    public function test_admin_can_create_bot(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/bots', [
            'name' => 'New Bot',
            'description' => 'Test description',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bots', [
            'name' => 'New Bot',
            'created_by' => $admin->id,
        ]);
    }

    public function test_editor_can_create_bot(): void
    {
        $editor = User::factory()->editor()->create();

        $response = $this->actingAs($editor)->post('/bots', [
            'name' => 'Editor Bot',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bots', ['name' => 'Editor Bot']);
    }

    public function test_viewer_cannot_create_bot(): void
    {
        $viewer = User::factory()->create();

        $this->actingAs($viewer)->post('/bots', ['name' => 'Fail'])
            ->assertForbidden();
    }

    public function test_editor_can_update_own_bot(): void
    {
        $editor = User::factory()->editor()->create();
        $bot = Bot::factory()->for($editor, 'creator')->create();

        $response = $this->actingAs($editor)->put("/bots/{$bot->id}", [
            'name' => 'Updated Bot',
        ]);

        $response->assertRedirect();
        $this->assertEquals('Updated Bot', $bot->fresh()->name);
    }

    public function test_editor_cannot_update_other_users_bot(): void
    {
        $editor = User::factory()->editor()->create();
        $otherBot = Bot::factory()->create();

        $this->actingAs($editor)->put("/bots/{$otherBot->id}", ['name' => 'Hacked'])
            ->assertForbidden();
    }

    public function test_only_admin_can_delete_bot(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();
        $bot = Bot::factory()->for($editor, 'creator')->create();

        $this->actingAs($editor)->delete("/bots/{$bot->id}")->assertForbidden();
        $this->actingAs($admin)->delete("/bots/{$bot->id}")->assertRedirect('/');
        $this->assertDatabaseMissing('bots', ['id' => $bot->id]);
    }

    public function test_bot_edit_page_loads_with_relations(): void
    {
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($admin, 'creator')->create();

        $response = $this->actingAs($admin)->get("/bots/{$bot->id}/edit");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Bots/Edit')
            ->has('bot')
            ->where('bot.id', $bot->id)
        );
    }

    public function test_create_bot_validates_name_is_required(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/bots', [])
            ->assertSessionHasErrors(['name']);
    }

    public function test_can_save_telegram_profile(): void
    {
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($admin, 'creator')->create([
            'messenger_config' => ['telegram' => ['enabled' => true]],
        ]);

        $response = $this->actingAs($admin)->put(route('bots.update', $bot), [
            'messenger_config' => [
                'telegram' => [
                    'enabled' => true,
                    'profile' => [
                        'name' => 'Govorun',
                        'short_description' => 'About',
                        'description' => 'Long description',
                    ],
                ],
            ],
        ]);

        $response->assertRedirect();
        $bot->refresh();
        $this->assertSame('Govorun', $bot->messenger_config['telegram']['profile']['name']);
    }

    public function test_validation_rejects_too_long_name(): void
    {
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($admin, 'creator')->create([
            'messenger_config' => ['telegram' => ['enabled' => true]],
        ]);

        $response = $this->actingAs($admin)->put(route('bots.update', $bot), [
            'messenger_config' => [
                'telegram' => [
                    'enabled' => true,
                    'profile' => ['name' => str_repeat('a', 65)],
                ],
            ],
        ]);

        $response->assertSessionHasErrors('messenger_config.telegram.profile.name');
    }
}
