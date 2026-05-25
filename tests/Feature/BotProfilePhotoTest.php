<?php

namespace Tests\Feature;

use App\Models\Bot;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Services\TelegramProfileVideoConverter;
use Illuminate\Foundation\Testing\RefreshDatabase;

/** Тесты загрузки/показа/удаления аватара Telegram-бота. */
class BotProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_jpg_photo(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($admin, 'creator')->create([
            'messenger_config' => ['telegram' => ['enabled' => true]],
        ]);

        $file = UploadedFile::fake()->image('avatar.jpg', 256, 256);

        $response = $this->actingAs($admin)
            ->post(route('bots.profile-photo.upload', $bot), ['file' => $file]);

        $response->assertRedirect();
        $bot->refresh();
        $path = $bot->messenger_config['telegram']['profile']['photo_path'];
        $this->assertNotEmpty($path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_png_is_converted_to_jpg(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($admin, 'creator')->create([
            'messenger_config' => ['telegram' => ['enabled' => true]],
        ]);

        $file = UploadedFile::fake()->image('avatar.png', 256, 256);

        $this->actingAs($admin)
            ->post(route('bots.profile-photo.upload', $bot), ['file' => $file])
            ->assertRedirect();

        $bot->refresh();
        $path = $bot->messenger_config['telegram']['profile']['photo_path'];
        $this->assertStringEndsWith('.jpg', $path);
    }

    public function test_oversize_file_is_rejected(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($admin, 'creator')->create([
            'messenger_config' => ['telegram' => ['enabled' => true]],
        ]);

        $file = UploadedFile::fake()->create('big.jpg', 11000, 'image/jpeg'); // 11 MB > 10 MB

        $this->actingAs($admin)
            ->post(route('bots.profile-photo.upload', $bot), ['file' => $file])
            ->assertSessionHasErrors('file');
    }

    public function test_mp4_upload_runs_through_video_converter(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($admin, 'creator')->create([
            'messenger_config' => ['telegram' => ['enabled' => true]],
        ]);

        $converterMock = $this->createMock(TelegramProfileVideoConverter::class);
        $converterMock->expects($this->once())
            ->method('convert')
            ->willReturnCallback(function (string $src, string $dest) {
                file_put_contents($dest, 'normalized-mp4-bytes');
            });
        $this->app->instance(TelegramProfileVideoConverter::class, $converterMock);

        $file = UploadedFile::fake()->create('avatar.mp4', 200, 'video/mp4');

        $this->actingAs($admin)
            ->post(route('bots.profile-photo.upload', $bot), ['file' => $file])
            ->assertRedirect();

        $bot->refresh();
        $path = $bot->messenger_config['telegram']['profile']['photo_path'];
        $this->assertStringEndsWith('.mp4', $path);
        $this->assertSame('normalized-mp4-bytes', Storage::disk('local')->get($path));
    }

    public function test_mp4_upload_returns_422_when_converter_fails(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($admin, 'creator')->create([
            'messenger_config' => ['telegram' => ['enabled' => true]],
        ]);

        $converterMock = $this->createMock(TelegramProfileVideoConverter::class);
        $converterMock->expects($this->once())
            ->method('convert')
            ->willThrowException(new \RuntimeException('ffmpeg не смог обработать видео.'));
        $this->app->instance(TelegramProfileVideoConverter::class, $converterMock);

        $file = UploadedFile::fake()->create('avatar.mp4', 200, 'video/mp4');

        $this->actingAs($admin)
            ->post(route('bots.profile-photo.upload', $bot), ['file' => $file])
            ->assertSessionHasErrors('file');

        $bot->refresh();
        $this->assertArrayNotHasKey('photo_path', $bot->messenger_config['telegram']['profile'] ?? []);
        Storage::disk('local')->assertMissing("bot-profiles/{$bot->id}/profile.mp4");
    }

    public function test_viewer_cannot_upload_photo(): void
    {
        Storage::fake('local');
        $owner = User::factory()->editor()->create();
        $viewer = User::factory()->viewer()->create();
        $bot = Bot::factory()->for($owner, 'creator')->create([
            'messenger_config' => ['telegram' => ['enabled' => true]],
        ]);

        $file = UploadedFile::fake()->image('avatar.jpg', 256, 256);

        $this->actingAs($viewer)
            ->post(route('bots.profile-photo.upload', $bot), ['file' => $file])
            ->assertForbidden();
    }

    public function test_show_returns_photo_to_authorized_user(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($admin, 'creator')->create([
            'messenger_config' => ['telegram' => ['enabled' => true]],
        ]);

        Storage::disk('local')->put("bot-profiles/{$bot->id}/profile.jpg", 'fake');
        $bot->messenger_config = [
            'telegram' => [
                'enabled' => true,
                'profile' => ['photo_path' => "bot-profiles/{$bot->id}/profile.jpg"],
            ],
        ];
        $bot->save();

        $this->actingAs($admin)
            ->get(route('bots.profile-photo.show', $bot))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_delete_removes_photo_and_clears_path(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($admin, 'creator')->create([
            'messenger_config' => ['telegram' => ['enabled' => true]],
        ]);
        Storage::disk('local')->put("bot-profiles/{$bot->id}/profile.jpg", 'fake');
        $bot->messenger_config = [
            'telegram' => [
                'enabled' => true,
                'profile' => ['photo_path' => "bot-profiles/{$bot->id}/profile.jpg"],
            ],
        ];
        $bot->save();

        $this->actingAs($admin)
            ->delete(route('bots.profile-photo.delete', $bot))
            ->assertRedirect();

        $bot->refresh();
        $this->assertNull($bot->messenger_config['telegram']['profile']['photo_path']);
        Storage::disk('local')->assertMissing("bot-profiles/{$bot->id}/profile.jpg");
    }
}
