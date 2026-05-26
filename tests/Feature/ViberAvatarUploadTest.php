<?php

namespace Tests\Feature;

use App\Models\Bot;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

/** Тесты загрузки/удаления/просмотра аватара Viber-бота. */
class ViberAvatarUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
    }

    public function test_admin_can_upload_viber_avatar(): void
    {
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($admin, 'creator')->create();

        $file = UploadedFile::fake()->image('avatar.jpg', 800, 800)->size(500);

        $this->actingAs($admin)
            ->post(route('bots.viber.avatar.upload', $bot), ['file' => $file])
            ->assertRedirect();

        $bot->refresh();
        $path = $bot->messenger_config['viber']['profile']['avatar_path'];
        $this->assertNotNull($path);
        $this->assertStringStartsWith("bots/{$bot->id}/viber/avatar.", $path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_upload_rejects_video(): void
    {
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($admin, 'creator')->create();

        $file = UploadedFile::fake()->create('clip.mp4', 100, 'video/mp4');

        $this->actingAs($admin)
            ->post(route('bots.viber.avatar.upload', $bot), ['file' => $file])
            ->assertSessionHasErrors('file');
    }

    public function test_admin_can_delete_viber_avatar(): void
    {
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($admin, 'creator')->create([
            'messenger_config' => [
                'viber' => [
                    'enabled' => true,
                    'profile' => ['avatar_path' => "bots/1/viber/avatar.jpg", 'sender_name' => 'Bot', 'event_types' => ['message']],
                ],
            ],
        ]);
        Storage::disk('local')->put($bot->messenger_config['viber']['profile']['avatar_path'], 'fake');

        $this->actingAs($admin)
            ->delete(route('bots.viber.avatar.delete', $bot))
            ->assertRedirect();

        $bot->refresh();
        $this->assertNull($bot->messenger_config['viber']['profile']['avatar_path']);
    }

    public function test_show_avatar_returns_file(): void
    {
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($admin, 'creator')->create([
            'messenger_config' => [
                'viber' => ['enabled' => true, 'profile' => ['avatar_path' => 'bots/1/viber/avatar.jpg']],
            ],
        ]);
        Storage::disk('local')->put('bots/1/viber/avatar.jpg', file_get_contents(__DIR__.'/../Fixtures/sample.jpg'));

        $this->actingAs($admin)
            ->get(route('bots.viber.avatar.show', $bot))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
    }
}