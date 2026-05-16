<?php

use App\Models\Bot;
use App\Models\User;
use App\Enums\UserRole;
use App\Models\BotConnection;
use App\Services\Http\SsrfGuard;
use App\Enums\ConnectionAuthType;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://api.example.com/*' => Http::response(['ok' => true], 200),
    ]);

    $ssrf = Mockery::mock(SsrfGuard::class);
    $ssrf->shouldReceive('isAllowed')->andReturn(true);
    app()->instance(SsrfGuard::class, $ssrf);
});

it('endpoint возвращает результат запроса', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $bot = Bot::factory()->create();
    $conn = BotConnection::factory()->for($bot)->create([
        'base_url' => 'https://api.example.com',
        'auth_type' => ConnectionAuthType::None->value,
    ]);

    $this->actingAs($admin)
        ->postJson(route('bot-connections.test', [$bot, $conn]), [
            'method' => 'GET',
            'path' => '/users',
            'body_mode' => 'none',
        ])
        ->assertOk()
        ->assertJson([
            'status' => 200,
            'body_json' => ['ok' => true],
        ]);
});

it('endpoint требует прав update', function () {
    $viewer = User::factory()->create(['role' => UserRole::Viewer]);
    $conn = BotConnection::factory()->for(Bot::factory()->create())->create();

    $this->actingAs($viewer)
        ->postJson(route('bot-connections.test', [$conn->bot, $conn]), [
            'method' => 'GET',
            'path' => '/x',
            'body_mode' => 'none',
        ])
        ->assertForbidden();
});
