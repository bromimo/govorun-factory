<?php

namespace Tests\Feature;

use App\Models\Bot;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/** Тесты страницы настроек профиля WhatsApp-бота. */
class WhatsAppProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_admin_can_view_whatsapp_profile_page(): void
    {
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($admin, 'creator')->create([
            'messenger_config' => ['whatsapp' => [
                'enabled' => true,
                'profile' => ['about' => 'Привет', 'vertical' => 'RETAIL'],
            ]],
        ]);

        $this->actingAs($admin)
            ->get(route('bots.whatsapp.profile.edit', $bot))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Bots/WhatsAppProfile', false)
                ->where('bot.messenger_config.whatsapp.profile.about', 'Привет'));
    }

    public function test_update_saves_profile(): void
    {
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($admin, 'creator')->create([
            'messenger_config' => ['whatsapp' => ['enabled' => true]],
        ]);

        $this->actingAs($admin)
            ->put(route('bots.whatsapp.profile.update', $bot), [
                'profile' => [
                    'about' => 'Новое',
                    'description' => 'Опис',
                    'vertical' => 'EDU',
                    'websites' => ['https://example.com'],
                ],
            ])
            ->assertRedirect();

        expect($bot->fresh()->messenger_config['whatsapp']['profile']['about'])->toBe('Новое');
    }

    public function test_update_validates_about_length(): void
    {
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($admin, 'creator')->create([
            'messenger_config' => ['whatsapp' => ['enabled' => true]],
        ]);

        $this->actingAs($admin)
            ->put(route('bots.whatsapp.profile.update', $bot), [
                'profile' => ['about' => str_repeat('я', 140), 'vertical' => 'RETAIL'],
            ])
            ->assertSessionHasErrors('profile.about');
    }

    public function test_viewer_cannot_update(): void
    {
        $owner = User::factory()->editor()->create();
        $viewer = User::factory()->viewer()->create();
        $bot = Bot::factory()->for($owner, 'creator')->create([
            'messenger_config' => ['whatsapp' => ['enabled' => true]],
        ]);

        $this->actingAs($viewer)
            ->put(route('bots.whatsapp.profile.update', $bot), ['profile' => ['vertical' => 'RETAIL']])
            ->assertForbidden();
    }
}
