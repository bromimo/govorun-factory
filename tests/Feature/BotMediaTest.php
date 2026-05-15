<?php

use App\Models\Bot;
use App\Models\BotMedia;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
});

test('admin can upload photo to bot', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $bot = Bot::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('bots.media.store', $bot), [
            'file' => UploadedFile::fake()->image('photo.jpg', 100, 100),
            'type' => 'photo',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['id', 'type', 'filename', 'size', 'width', 'height', 'file_url']);

    expect(BotMedia::where('bot_id', $bot->id)->count())->toBe(1);
});

test('upload stores optimized file on disk', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $bot = Bot::factory()->create();

    $this->actingAs($user)
        ->postJson(route('bots.media.store', $bot), [
            'file' => UploadedFile::fake()->image('photo.png', 100, 100),
            'type' => 'photo',
        ]);

    $media = BotMedia::where('bot_id', $bot->id)->first();
    Storage::disk('local')->assertExists("media/{$bot->id}/{$media->filename}");
    expect($media->mime_type)->toBe('image/jpeg');
});

test('index returns json list when wantsJson', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $bot = Bot::factory()->create();
    BotMedia::factory()->count(3)->create(['bot_id' => $bot->id]);

    $this->actingAs($user)
        ->getJson(route('bots.media.index', $bot))
        ->assertOk()
        ->assertJsonCount(3);
});

test('destroy deletes record and file', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $bot = Bot::factory()->create();
    $media = BotMedia::factory()->create(['bot_id' => $bot->id, 'filename' => 'test.jpg']);
    Storage::disk('local')->put("media/{$bot->id}/test.jpg", 'content');

    $this->actingAs($user)
        ->deleteJson(route('bots.media.destroy', [$bot, $media]))
        ->assertOk();

    expect(BotMedia::find($media->id))->toBeNull();
    Storage::disk('local')->assertMissing("media/{$bot->id}/test.jpg");
});

test('viewer cannot upload media', function () {
    $user = User::factory()->create(['role' => 'viewer']);
    $bot = Bot::factory()->create();

    $this->actingAs($user)
        ->postJson(route('bots.media.store', $bot), [
            'file' => UploadedFile::fake()->image('photo.jpg'),
            'type' => 'photo',
        ])
        ->assertForbidden();
});

test('file endpoint returns 404 for media from different bot', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $bot1 = Bot::factory()->create();
    $bot2 = Bot::factory()->create();
    $media = BotMedia::factory()->create(['bot_id' => $bot2->id, 'filename' => 'test.jpg']);
    Storage::disk('local')->put("media/{$bot2->id}/test.jpg", 'content');

    $this->actingAs($user)
        ->get(route('bots.media.file', [$bot1, $media]))
        ->assertNotFound();
});
