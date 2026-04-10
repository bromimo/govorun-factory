<?php

use App\Models\Bot;
use App\Models\BotRoute;
use App\Models\User;
use App\Services\SchemaValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('passes when bot has routes and valid config', function () {
    $admin = User::factory()->admin()->create();
    $bot = Bot::factory()->for($admin, 'creator')->create([
        'messenger_config' => ['telegram' => ['token' => 'test']],
    ]);
    BotRoute::factory()->for($bot)->create();

    $validator = new SchemaValidator($bot);
    $result = $validator->validate();

    expect($result->isValid())->toBeTrue();
});

test('fails when bot has no routes', function () {
    $admin = User::factory()->admin()->create();
    $bot = Bot::factory()->for($admin, 'creator')->create([
        'messenger_config' => ['telegram' => ['token' => 'test']],
    ]);

    $validator = new SchemaValidator($bot);
    $result = $validator->validate();

    expect($result->isValid())->toBeFalse();
    expect($result->errors)->toContain('Бот должен иметь хотя бы один маршрут');
});

test('fails when messenger config is empty', function () {
    $admin = User::factory()->admin()->create();
    $bot = Bot::factory()->for($admin, 'creator')->create([
        'messenger_config' => [],
    ]);
    BotRoute::factory()->for($bot)->create();

    $validator = new SchemaValidator($bot);
    $result = $validator->validate();

    expect($result->isValid())->toBeFalse();
    expect($result->errors)->toContain('Необходимо настроить хотя бы один мессенджер');
});

test('fails when flow has no steps', function () {
    $admin = User::factory()->admin()->create();
    $bot = Bot::factory()->for($admin, 'creator')->create([
        'messenger_config' => ['telegram' => ['token' => 'test']],
    ]);
    BotRoute::factory()->for($bot)->create();
    $bot->flows()->create([
        'name' => 'EmptyFlow',
        'graph' => ['nodes' => [], 'edges' => []],
    ]);

    $validator = new SchemaValidator($bot);
    $result = $validator->validate();

    expect($result->isValid())->toBeFalse();
    expect($result->errors[0])->toContain('EmptyFlow');
});

test('fails when flow has no on_complete node', function () {
    $admin = User::factory()->admin()->create();
    $bot = Bot::factory()->for($admin, 'creator')->create([
        'messenger_config' => ['telegram' => ['token' => 'test']],
    ]);
    BotRoute::factory()->for($bot)->create();
    $bot->flows()->create([
        'name' => 'NoComplete',
        'graph' => [
            'nodes' => [
                ['id' => 's1', 'type' => 'ask_text', 'data' => ['text' => 'Hi'], 'position' => ['x' => 0, 'y' => 0]],
            ],
            'edges' => [],
        ],
    ]);

    $validator = new SchemaValidator($bot);
    $result = $validator->validate();

    expect($result->isValid())->toBeFalse();
    expect($result->errors[0])->toContain('on_complete');
});
