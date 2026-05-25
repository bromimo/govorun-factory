<?php

use App\Models\Bot;
use App\Models\BotFlow;
use App\Models\BotRoute;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Запустить миграцию по конкретному пути (идемпотентно).
 */
function runUnifyBlocksMigration(): void
{
    $migrationPath = database_path('migrations/2026_04_27_000001_unify_ask_reply_blocks.php');
    $migration = require $migrationPath;
    $migration->up();
}

test('migration converts ask_text node to ask with mode=text', function () {
    $bot = Bot::factory()->create();
    $flow = BotFlow::factory()->for($bot)->create([
        'graph' => ['nodes' => [
            ['id' => 'a', 'type' => 'ask_text', 'data' => [
                'text' => 'Имя?', 'image' => '', 'validation' => [],
            ], 'position' => ['x' => 0, 'y' => 0]],
        ], 'edges' => []],
    ]);

    runUnifyBlocksMigration();

    $flow->refresh();
    $node = $flow->graph['nodes'][0];

    expect($node['type'])->toBe('ask');
    expect($node['data']['mode'])->toBe('text');
    expect($node['data']['text'])->toBe('Имя?');
    expect($node['data']['media'])->toBeNull();
    expect($node['data']['validation'])->toBe([]);
    expect($node['data']['keyboard'])->toBeNull();
});

test('migration converts ask_keyboard with image to ask with mode=callback and media', function () {
    $bot = Bot::factory()->create();
    $flow = BotFlow::factory()->for($bot)->create([
        'graph' => ['nodes' => [
            ['id' => 'a', 'type' => 'ask_keyboard', 'data' => [
                'text' => 'Цвет?',
                'image' => 'https://x/img.jpg',
                'buttons' => [[['type' => 'action', 'label' => 'R', 'action' => 'r']]],
                'validation' => [],
            ], 'position' => ['x' => 0, 'y' => 0]],
        ], 'edges' => []],
    ]);

    runUnifyBlocksMigration();

    $flow->refresh();
    $node = $flow->graph['nodes'][0];

    expect($node['type'])->toBe('ask');
    expect($node['data']['mode'])->toBe('callback');
    expect($node['data']['media'])->toBe(['type' => 'photo', 'url' => 'https://x/img.jpg']);
    expect($node['data']['keyboard'])->toBe([
        'type' => 'inline',
        'buttons' => [[['type' => 'action', 'label' => 'R', 'action' => 'r']]],
    ]);
});

test('migration converts reply_media to reply with media and caption-as-text', function () {
    $bot = Bot::factory()->create();
    $route = BotRoute::factory()->for($bot)->create([
        'handler_schema' => ['blocks' => [
            ['type' => 'reply_media', 'params' => [
                'media_type' => 'video',
                'url' => 'https://x/y.mp4',
                'caption' => 'Подпись',
            ]],
        ]],
    ]);

    runUnifyBlocksMigration();

    $route->refresh();
    $block = $route->handler_schema['blocks'][0];

    expect($block['type'])->toBe('reply');
    expect($block['params']['text'])->toBe('Подпись');
    expect($block['params']['media'])->toBe(['type' => 'video', 'url' => 'https://x/y.mp4']);
});

test('migration is idempotent — already-unified types are skipped', function () {
    $bot = Bot::factory()->create();
    $route = BotRoute::factory()->for($bot)->create([
        'handler_schema' => ['blocks' => [
            ['type' => 'reply', 'params' => ['text' => 'Hi', 'media' => null, 'keyboard' => null]],
        ]],
    ]);

    runUnifyBlocksMigration();

    $route->refresh();
    expect($route->handler_schema['blocks'][0])->toBe(
        ['type' => 'reply', 'params' => ['text' => 'Hi', 'media' => null, 'keyboard' => null]]
    );
});
