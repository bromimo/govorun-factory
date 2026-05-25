<?php

use App\Models\Bot;
use App\Models\BotConnection;
use App\Services\Http\SsrfGuard;
use App\Enums\ConnectionAuthType;
use App\Services\Http\RequestDraft;
use Illuminate\Support\Facades\Http;
use App\Services\Http\ConnectionRequestService;
use App\Services\Http\TemplatePlaceholderRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::preventStrayRequests();

    $ssrf = Mockery::mock(SsrfGuard::class);
    $ssrf->shouldReceive('isAllowed')
        ->withArgs(fn ($url) => str_starts_with($url, 'https://api.example.com'))
        ->andReturn(true);
    $ssrf->shouldReceive('isAllowed')
        ->withArgs(fn ($url) => str_starts_with($url, 'http://127.0.0.1'))
        ->andReturn(false);

    $this->service = new ConnectionRequestService($ssrf, new TemplatePlaceholderRenderer);
});

it('собирает URL из base_url + path и подставляет state', function () {
    Http::fake([
        'https://api.example.com/users/42' => Http::response(['name' => 'Иван'], 200),
    ]);

    $conn = BotConnection::factory()->for(Bot::factory()->create())->create([
        'base_url' => 'https://api.example.com',
        'auth_type' => ConnectionAuthType::None->value,
    ]);

    $draft = new RequestDraft(
        method: 'GET',
        path: '/users/{{state.user_id}}',
        stateSample: ['user_id' => '42'],
    );

    $result = $this->service->execute($conn, $draft);

    expect($result->status)->toBe(200);
    expect($result->bodyJson)->toBe(['name' => 'Иван']);
});

it('добавляет Bearer-заголовок', function () {
    Http::fake([
        'https://api.example.com/*' => Http::response([], 200),
    ]);

    $conn = BotConnection::factory()->for(Bot::factory()->create())->create([
        'base_url' => 'https://api.example.com',
        'auth_type' => ConnectionAuthType::Bearer->value,
        'auth_config' => ['token' => 'tok-xyz'],
    ]);

    $this->service->execute($conn, new RequestDraft(method: 'GET', path: '/me'));

    Http::assertSent(fn ($req) => $req->hasHeader('Authorization', 'Bearer tok-xyz'));
});

it('блокирует SSRF', function () {
    $conn = BotConnection::factory()->for(Bot::factory()->create())->create([
        'base_url' => 'http://127.0.0.1:8080',
    ]);

    $result = $this->service->execute($conn, new RequestDraft(method: 'GET', path: '/x'));

    expect($result->status)->toBeNull();
    expect($result->error)->toContain('запрещён');
});

it('обрезает тело при превышении лимита', function () {
    $bigBody = str_repeat('x', 1024 * 1024 + 100);
    Http::fake([
        'https://api.example.com/*' => Http::response($bigBody, 200, ['Content-Type' => 'text/plain']),
    ]);

    $conn = BotConnection::factory()->for(Bot::factory()->create())->create([
        'base_url' => 'https://api.example.com',
        'auth_type' => ConnectionAuthType::None->value,
    ]);

    $result = $this->service->execute($conn, new RequestDraft(method: 'GET', path: '/big'));

    expect($result->truncated)->toBeTrue();
    expect(strlen($result->bodyRaw))->toBeLessThanOrEqual(1024 * 1024);
});
