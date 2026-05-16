<?php

use App\Models\Bot;
use App\Models\BotConnection;
use App\Enums\ConnectionAuthType;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('шифрует auth_config через encrypted cast', function () {
    $bot = Bot::factory()->create();

    $connection = BotConnection::create([
        'bot_id' => $bot->id,
        'name' => 'Bitrix24',
        'slug' => 'bitrix24',
        'base_url' => 'https://example.com',
        'auth_type' => ConnectionAuthType::Bearer->value,
        'auth_config' => ['token' => 'secret-token-123'],
    ]);

    $raw = DB::table('bot_connections')->where('id', $connection->id)->value('auth_config');

    expect($raw)->not->toContain('secret-token-123');
    expect($connection->fresh()->auth_config)->toBe(['token' => 'secret-token-123']);
});

it('связан с ботом и возвращается через bot.connections', function () {
    $bot = Bot::factory()->create();
    BotConnection::factory()->for($bot)->create(['name' => 'A']);
    BotConnection::factory()->for($bot)->create(['name' => 'B']);

    expect($bot->connections)->toHaveCount(2);
});

it('каскадно удаляется с ботом', function () {
    $bot = Bot::factory()->create();
    $conn = BotConnection::factory()->for($bot)->create();

    $bot->delete();

    expect(BotConnection::find($conn->id))->toBeNull();
});
