<?php

namespace Tests\Feature;

use App\Models\Bot;
use Tests\TestCase;
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
            'type' => 'command',
            'match' => '/start',
            'description' => 'Запуск',
            'handler_type' => 'controller',
            'handler_schema' => ['blocks' => []],
            'sort_order' => 0,
        ]);
        $bot->routes()->create([
            'type' => 'phrase',
            'match' => 'привет',
            'description' => null,
            'handler_type' => 'controller',
            'handler_schema' => ['blocks' => []],
            'sort_order' => 1,
        ]);

        $this->actingAs($admin)
            ->get(route('bots.telegram.profile.edit', $bot))
            ->assertInertia(fn ($page) => $page
                ->has('bot.routes', 1)
                ->where('bot.routes.0.match', '/start')
            );
    }

    public function test_admin_can_update_telegram_profile(): void
    {
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($admin, 'creator')->create([
            'messenger_config' => [
                'telegram' => [
                    'enabled' => true,
                    'username' => 'mybot',
                    'profile' => [
                        'name' => 'Old',
                        'short_description' => 'old short',
                        'description' => 'old desc',
                        'photo_path' => null,
                    ],
                ],
                'vk' => ['enabled' => false],
            ],
        ]);

        $this->actingAs($admin)
            ->put(route('bots.telegram.profile.update', $bot), [
                'profile' => [
                    'name' => 'New',
                    'short_description' => 'new short',
                    'description' => 'new desc',
                    'photo_path' => null,
                ],
            ])
            ->assertRedirect();

        $bot->refresh();
        $this->assertSame('New', $bot->messenger_config['telegram']['profile']['name']);
        $this->assertSame('new short', $bot->messenger_config['telegram']['profile']['short_description']);
        $this->assertSame('mybot', $bot->messenger_config['telegram']['username']);
        $this->assertFalse($bot->messenger_config['vk']['enabled']);
    }

    public function test_update_preserves_existing_photo_path(): void
    {
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($admin, 'creator')->create([
            'messenger_config' => [
                'telegram' => [
                    'enabled' => true,
                    'profile' => ['photo_path' => 'bot-profiles/1/profile.jpg'],
                ],
            ],
        ]);

        $this->actingAs($admin)
            ->put(route('bots.telegram.profile.update', $bot), [
                'profile' => [
                    'name' => 'Bot',
                    'short_description' => '',
                    'description' => '',
                    'photo_path' => 'bot-profiles/1/profile.jpg',
                ],
            ])
            ->assertRedirect();

        $bot->refresh();
        $this->assertSame('bot-profiles/1/profile.jpg', $bot->messenger_config['telegram']['profile']['photo_path']);
    }

    public function test_update_validates_name_max_length(): void
    {
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($admin, 'creator')->create();

        $this->actingAs($admin)
            ->put(route('bots.telegram.profile.update', $bot), [
                'profile' => [
                    'name' => str_repeat('a', 65),
                    'short_description' => '',
                    'description' => '',
                    'photo_path' => null,
                ],
            ])
            ->assertSessionHasErrors('profile.name');
    }

    public function test_update_validates_short_description_max_length(): void
    {
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($admin, 'creator')->create();

        $this->actingAs($admin)
            ->put(route('bots.telegram.profile.update', $bot), [
                'profile' => [
                    'name' => 'Bot',
                    'short_description' => str_repeat('a', 121),
                    'description' => '',
                    'photo_path' => null,
                ],
            ])
            ->assertSessionHasErrors('profile.short_description');
    }

    public function test_update_validates_description_max_length(): void
    {
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($admin, 'creator')->create();

        $this->actingAs($admin)
            ->put(route('bots.telegram.profile.update', $bot), [
                'profile' => [
                    'name' => 'Bot',
                    'short_description' => '',
                    'description' => str_repeat('a', 513),
                    'photo_path' => null,
                ],
            ])
            ->assertSessionHasErrors('profile.description');
    }

    public function test_viewer_cannot_update_telegram_profile(): void
    {
        $owner = User::factory()->editor()->create();
        $viewer = User::factory()->viewer()->create();
        $bot = Bot::factory()->for($owner, 'creator')->create();

        $this->actingAs($viewer)
            ->put(route('bots.telegram.profile.update', $bot), [
                'profile' => [
                    'name' => 'Hack',
                    'short_description' => '',
                    'description' => '',
                    'photo_path' => null,
                ],
            ])
            ->assertForbidden();
    }

    public function test_editor_cannot_update_foreign_bot_telegram_profile(): void
    {
        $owner = User::factory()->editor()->create();
        $other = User::factory()->editor()->create();
        $bot = Bot::factory()->for($owner, 'creator')->create();

        $this->actingAs($other)
            ->put(route('bots.telegram.profile.update', $bot), [
                'profile' => [
                    'name' => 'Hack',
                    'short_description' => '',
                    'description' => '',
                    'photo_path' => null,
                ],
            ])
            ->assertForbidden();
    }
}
