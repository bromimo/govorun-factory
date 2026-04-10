<?php

use App\Models\Bot;
use App\Models\BotRoute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can export valid bot as ZIP', function () {
    $admin = User::factory()->admin()->create();
    $bot = Bot::factory()->for($admin, 'creator')->create([
        'messenger_config' => ['telegram'],
    ]);
    BotRoute::factory()->for($bot)->create();

    $response = $this->actingAs($admin)->get("/bots/{$bot->id}/export");

    $response->assertOk();
    $response->assertHeader('content-type', 'application/zip');
});

test('export fails with validation errors for invalid bot', function () {
    $admin = User::factory()->admin()->create();
    $bot = Bot::factory()->for($admin, 'creator')->create([
        'messenger_config' => [],
    ]);

    $response = $this->actingAs($admin)->get("/bots/{$bot->id}/export");

    $response->assertStatus(422);
    $response->assertJsonStructure(['errors']);
});

test('viewer cannot export', function () {
    $viewer = User::factory()->create();
    $bot = Bot::factory()->create();

    $this->actingAs($viewer)->get("/bots/{$bot->id}/export")->assertForbidden();
});
