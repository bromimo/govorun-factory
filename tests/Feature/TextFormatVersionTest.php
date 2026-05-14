<?php

use App\Models\Bot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('new bot has text_format_version 2 by default', function () {
    $user = User::factory()->admin()->create();
    $bot = Bot::factory()->for($user, 'creator')->create();

    expect($bot->fresh()->text_format_version)->toBe(2);
});

test('existing bot can have text_format_version 1', function () {
    $user = User::factory()->admin()->create();
    $bot = Bot::factory()->for($user, 'creator')->create(['text_format_version' => 1]);

    expect($bot->fresh()->text_format_version)->toBe(1);
});
