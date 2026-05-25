<?php

use App\Models\Bot;
use App\Models\User;
use App\Enums\UserRole;
use App\Models\BotConnection;
use App\Enums\ConnectionAuthType;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->bot = Bot::factory()->create();
});

it('index возвращает список подключений с маскированными секретами', function () {
    BotConnection::factory()->for($this->bot)->create([
        'name' => 'Bitrix',
        'slug' => 'bitrix',
        'auth_type' => ConnectionAuthType::Bearer->value,
        'auth_config' => ['token' => 'super-secret-token-abcd'],
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('bots.edit', $this->bot));

    $response->assertOk();
    $page = $response->viewData('page');
    $connection = $page['props']['connections'][0];

    expect($connection['name'])->toBe('Bitrix');
    expect($connection['auth_config_preview'])->toBe('••••abcd');
    expect(json_encode($connection))->not->toContain('super-secret-token-abcd');
});

it('store создаёт подключение', function () {
    $this->actingAs($this->admin)
        ->post(route('bot-connections.store', $this->bot), [
            'name' => 'Test',
            'slug' => 'test_conn',
            'base_url' => 'https://api.example.com',
            'auth_type' => 'bearer',
            'auth_config' => ['token' => 'tok-1234'],
        ])
        ->assertRedirect();

    expect(BotConnection::where('slug', 'test_conn')->exists())->toBeTrue();
});

it('store запрещает дубликат slug в рамках одного бота', function () {
    BotConnection::factory()->for($this->bot)->create(['slug' => 'dup']);

    $this->actingAs($this->admin)
        ->post(route('bot-connections.store', $this->bot), [
            'name' => 'X',
            'slug' => 'dup',
            'base_url' => 'https://example.com',
            'auth_type' => 'none',
        ])
        ->assertSessionHasErrors('slug');
});

it('update с пустым токеном не затирает существующий', function () {
    $conn = BotConnection::factory()->for($this->bot)->create([
        'auth_type' => ConnectionAuthType::Bearer->value,
        'auth_config' => ['token' => 'original-tok'],
    ]);

    $this->actingAs($this->admin)
        ->put(route('bot-connections.update', [$this->bot, $conn]), [
            'name' => 'Renamed',
            'auth_config' => ['token' => ''],
        ])
        ->assertRedirect();

    expect($conn->fresh()->auth_config)->toBe(['token' => 'original-tok']);
});

it('destroy удаляет подключение', function () {
    $conn = BotConnection::factory()->for($this->bot)->create();

    $this->actingAs($this->admin)
        ->delete(route('bot-connections.destroy', [$this->bot, $conn]))
        ->assertRedirect();

    expect(BotConnection::find($conn->id))->toBeNull();
});

it('editor чужого бота получает 403', function () {
    $editor = User::factory()->create(['role' => UserRole::Editor]);
    $otherBot = Bot::factory()->create();
    $conn = BotConnection::factory()->for($otherBot)->create();

    $this->actingAs($editor)
        ->put(route('bot-connections.update', [$otherBot, $conn]), ['name' => 'x'])
        ->assertForbidden();
});

it('create страница доступна admin', function () {
    $this->actingAs($this->admin)
        ->get(route('bot-connections.create', $this->bot))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Bots/Connections/Edit', false)
            ->where('connection', null)
            ->where('can.update', true)
        );
});

it('edit страница загружает подключение', function () {
    $conn = BotConnection::factory()->for($this->bot)->create(['name' => 'Bitrix']);

    $this->actingAs($this->admin)
        ->get(route('bot-connections.edit', [$this->bot, $conn]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Bots/Connections/Edit', false)
            ->where('connection.id', $conn->id)
            ->where('connection.name', 'Bitrix')
            ->where('can.update', true)
        );
});

it('edit возвращает 404 для подключения чужого бота', function () {
    $otherBot = Bot::factory()->create();
    $conn = BotConnection::factory()->for($otherBot)->create();

    $this->actingAs($this->admin)
        ->get(route('bot-connections.edit', [$this->bot, $conn]))
        ->assertNotFound();
});

it('viewer на edit видит can.update = false', function () {
    $viewer = User::factory()->create(['role' => UserRole::Viewer]);
    $conn = BotConnection::factory()->for($this->bot)->create();

    $this->actingAs($viewer)
        ->get(route('bot-connections.edit', [$this->bot, $conn]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('can.update', false)
        );
});

it('viewer не может открыть create', function () {
    $viewer = User::factory()->create(['role' => UserRole::Viewer]);

    $this->actingAs($viewer)
        ->get(route('bot-connections.create', $this->bot))
        ->assertForbidden();
});

it('update после сохранения возвращает back()', function () {
    $conn = BotConnection::factory()->for($this->bot)->create();
    $referer = route('bot-connections.edit', [$this->bot, $conn]);

    $this->actingAs($this->admin)
        ->from($referer)
        ->put(route('bot-connections.update', [$this->bot, $conn]), [
            'name' => 'Renamed',
        ])
        ->assertRedirect($referer);
});
