<?php

namespace Tests\Unit\Observers;

use Tests\TestCase;
use App\Models\Bot;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

/** Тест каскадной очистки каталога с аватаром при удалении бота. */
class BotObserverProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_bot_removes_profile_photo_directory(): void
    {
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($admin, 'creator')->create();

        Storage::disk('local')->put("bot-profiles/{$bot->id}/profile.jpg", 'fake');
        Storage::disk('local')->assertExists("bot-profiles/{$bot->id}/profile.jpg");

        $bot->delete();

        Storage::disk('local')->assertMissing("bot-profiles/{$bot->id}/profile.jpg");
    }
}
