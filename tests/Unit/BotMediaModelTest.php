<?php

use App\Models\Bot;
use App\Models\BotMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('bot has media relationship', function () {
    $bot = Bot::factory()->create();
    $media = BotMedia::factory()->create(['bot_id' => $bot->id]);

    expect($bot->media->first()->id)->toBe($media->id);
    expect($media->bot->id)->toBe($bot->id);
});

test('bot media is deleted when bot is deleted', function () {
    $bot = Bot::factory()->create();
    BotMedia::factory()->create(['bot_id' => $bot->id]);

    $bot->delete();

    expect(BotMedia::where('bot_id', $bot->id)->count())->toBe(0);
});
