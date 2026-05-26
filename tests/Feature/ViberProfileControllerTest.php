<?php

namespace Tests\Feature;

use App\Models\Bot;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/** Тесты страницы настроек профиля Viber-бота. */
class ViberProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_admin_can_view_viber_profile_page(): void
    {
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($admin, 'creator')->create([
            'messenger_config' => [
                'viber' => [
                    'enabled' => true,
                    'profile' => [
                        'sender_name' => 'MyBot',
                        'public_account_uri' => 'mybot',
                        'event_types' => ['message', 'subscribed'],
                        'avatar_path' => null,
                    ],
                ],
            ],
        ]);

        $this->actingAs($admin)
            ->get(route('bots.viber.profile.edit', $bot))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Bots/ViberProfile', false)
                ->where('bot.id', $bot->id)
                ->where('bot.messenger_config.viber.profile.sender_name', 'MyBot')
            );
    }

    public function test_admin_can_update_viber_profile(): void
    {
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($admin, 'creator')->create([
            'messenger_config' => [
                'viber' => ['enabled' => true, 'profile' => ['sender_name' => 'Old']],
            ],
        ]);

        $this->actingAs($admin)
            ->put(route('bots.viber.profile.update', $bot), [
                'profile' => [
                    'sender_name' => 'New',
                    'public_account_uri' => 'newbot',
                    'event_types' => ['message', 'conversation_started'],
                    'avatar_path' => null,
                ],
            ])
            ->assertRedirect();

        $bot->refresh();
        $this->assertSame('New', $bot->messenger_config['viber']['profile']['sender_name']);
        $this->assertSame('newbot', $bot->messenger_config['viber']['profile']['public_account_uri']);
        $this->assertSame(['message', 'conversation_started'], $bot->messenger_config['viber']['profile']['event_types']);
    }

    public function test_update_validates_sender_name_required(): void
    {
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($admin, 'creator')->create();

        $this->actingAs($admin)
            ->put(route('bots.viber.profile.update', $bot), [
                'profile' => [
                    'sender_name' => '',
                    'event_types' => ['message'],
                ],
            ])
            ->assertSessionHasErrors('profile.sender_name');
    }

    public function test_update_validates_sender_name_max_length(): void
    {
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($admin, 'creator')->create();

        $this->actingAs($admin)
            ->put(route('bots.viber.profile.update', $bot), [
                'profile' => [
                    'sender_name' => str_repeat('a', 29),
                    'event_types' => ['message'],
                ],
            ])
            ->assertSessionHasErrors('profile.sender_name');
    }

    public function test_update_validates_event_types_in_whitelist(): void
    {
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($admin, 'creator')->create();

        $this->actingAs($admin)
            ->put(route('bots.viber.profile.update', $bot), [
                'profile' => [
                    'sender_name' => 'Bot',
                    'event_types' => ['some_unknown_event'],
                ],
            ])
            ->assertSessionHasErrors('profile.event_types.0');
    }

    public function test_viewer_cannot_update_viber_profile(): void
    {
        $owner = User::factory()->editor()->create();
        $viewer = User::factory()->viewer()->create();
        $bot = Bot::factory()->for($owner, 'creator')->create();

        $this->actingAs($viewer)
            ->put(route('bots.viber.profile.update', $bot), [
                'profile' => [
                    'sender_name' => 'Hack',
                    'event_types' => ['message'],
                ],
            ])
            ->assertForbidden();
    }

    public function test_editor_cannot_update_foreign_bot_viber_profile(): void
    {
        $owner = User::factory()->editor()->create();
        $other = User::factory()->editor()->create();
        $bot = Bot::factory()->for($owner, 'creator')->create();

        $this->actingAs($other)
            ->put(route('bots.viber.profile.update', $bot), [
                'profile' => [
                    'sender_name' => 'Hack',
                    'event_types' => ['message'],
                ],
            ])
            ->assertForbidden();
    }
}