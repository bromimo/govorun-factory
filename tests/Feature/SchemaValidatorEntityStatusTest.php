<?php

use App\Models\Bot;
use App\Models\User;
use App\Models\BotFlow;
use App\Models\BotRoute;
use App\Services\SchemaValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('full validation skips non-active routes', function () {
    $bot = Bot::factory()->for(User::factory()->admin(), 'creator')->create([
        'messenger_config' => ['telegram' => ['token' => 'x']],
    ]);

    BotRoute::factory()->for($bot)->create([
        'type' => 'command',
        'match' => '/start',
        'handler_type' => 'controller',
        'handler_schema' => ['blocks' => []],
        'status' => 'inactive',
    ]);
    BotRoute::factory()->for($bot)->create([
        'type' => 'command',
        'match' => '/active',
        'controller_name' => 'ActiveCmd',
        'handler_type' => 'controller',
        'handler_schema' => ['blocks' => [['type' => 'reply', 'params' => ['text' => 'hi']]]],
        'status' => 'active',
    ]);

    $result = (new SchemaValidator($bot))->validate();

    expect($result->errors)->toBeEmpty();
});

test('validateSingleRoute reports errors for empty controller blocks', function () {
    $bot = Bot::factory()->for(User::factory()->admin(), 'creator')->create([
        'messenger_config' => ['telegram' => ['token' => 'x']],
    ]);
    $route = BotRoute::factory()->for($bot)->create([
        'type' => 'command',
        'match' => '/start',
        'handler_type' => 'controller',
        'handler_schema' => ['blocks' => []],
    ]);

    $result = (new SchemaValidator($bot))->validateSingleRoute($route);

    expect(implode(' ', $result->errors))->toContain('нет ни одного блока');
});

test('active route referencing inactive flow yields validation error', function () {
    $bot = Bot::factory()->for(User::factory()->admin(), 'creator')->create([
        'messenger_config' => ['telegram' => ['token' => 'x']],
    ]);
    $flow = BotFlow::factory()->for($bot)->create([
        'graph' => ['nodes' => [['id' => 'start', 'type' => 'start']], 'edges' => []],
        'status' => 'inactive',
    ]);
    $route = BotRoute::factory()->for($bot)->create([
        'type' => 'command',
        'match' => '/start',
        'handler_type' => 'flow',
        'flow_id' => $flow->id,
        'status' => 'active',
    ]);

    $result = (new SchemaValidator($bot))->validateSingleRoute($route);

    expect(implode(' ', $result->errors))->toContain('неактивный диалог');
});

test('validateSingleFlow reports error when flow has no on_complete', function () {
    $bot = Bot::factory()->for(User::factory()->admin(), 'creator')->create([
        'messenger_config' => ['telegram' => ['token' => 'x']],
    ]);
    $flow = BotFlow::factory()->for($bot)->create([
        'graph' => ['nodes' => [['id' => 'start', 'type' => 'start']], 'edges' => []],
    ]);

    $result = (new SchemaValidator($bot))->validateSingleFlow($flow);

    expect(implode(' ', $result->errors))->toContain('on_complete');
});
