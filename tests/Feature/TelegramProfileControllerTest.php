<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Bot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/** Тесты страницы настроек профиля Telegram-бота. */
class TelegramProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_admin_can_view_telegram_profile_page(): void
    {
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($admin, 'creator')->create([
            'messenger_config' => [
                'telegram' => [
                    'enabled' => true,
                    'username' => 'mybot',
                    'profile' => [
                        'name' => 'My Bot',
                        'short_description' => 'short',
                        'description' => 'long',
                        'photo_path' => null,
                    ],
                ],
            ],
        ]);

        $this->actingAs($admin)
            ->get(route('bots.telegram.profile.edit', $bot))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Bots/TelegramProfile', false)
                ->where('bot.id', $bot->id)
                ->where('bot.messenger_config.telegram.profile.name', 'My Bot')
            );
    }

    public function test_editor_can_view_own_bot_telegram_profile_page(): void
    {
        $editor = User::factory()->editor()->create();
        $bot = Bot::factory()->for($editor, 'creator')->create();

        $this->actingAs($editor)
            ->get(route('bots.telegram.profile.edit', $bot))
            ->assertOk();
    }

    public function test_viewer_can_view_telegram_profile_page(): void
    {
        $owner = User::factory()->editor()->create();
        $viewer = User::factory()->viewer()->create();
        $bot = Bot::factory()->for($owner, 'creator')->create();

        $this->actingAs($viewer)
            ->get(route('bots.telegram.profile.edit', $bot))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('can.update', false)
            );
    }

    public function test_edit_loads_only_command_routes(): void
    {
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($admin, 'creator')->create();

        $bot->routes()->create([
            'type' => 'command', 'match' => '/start', 'description' => 'Запуск',
            'handler_type' => 'controller', 'handler_schema' => ['blocks' => []], 'sort_order' => 0,
        ]);
        $bot->routes()->create([
            'type' => 'phrase', 'match' => 'привет', 'description' => null,
            'handler_type' => 'controller', 'handler_schema' => ['blocks' => []], 'sort_order' => 1,
        ]);

        $this->actingAs($admin)
            ->get(route('bots.telegram.profile.edit', $bot))
            ->assertInertia(fn ($page) => $page
                ->has('bot.routes', 1)
                ->where('bot.routes.0.match', '/start')
            );
    }
}