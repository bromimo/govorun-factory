<?php

use App\Models\Bot;
use App\Models\BotFlow;
use App\Models\BotRoute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Запустить миграцию экранирования plain-text.
 * @return void
 */
function runEscapeMigration(): void
{
    $migration = require database_path('migrations/2026_05_14_000001_escape_existing_message_text.php');
    $migration->up();
}

test('escapes HTML chars in ask and reply text for version-1 bots', function () {
    $user = User::factory()->admin()->create();
    $bot = Bot::factory()->for($user, 'creator')->create(['text_format_version' => 1]);

    $flow = BotFlow::factory()->for($bot)->create([
        'graph' => [
            'nodes' => [
                [
                    'id' => 'n1', 'type' => 'ask', 'position' => ['x' => 0, 'y' => 0],
                    'data' => [
                        'mode' => 'text', 'stepName' => '', 'media' => null,
                        'validation' => [], 'keyboard' => null,
                        'text' => 'Hi <user>! {{user.firstName}}',
                    ],
                ],
                [
                    'id' => 'n2', 'type' => 'reply', 'position' => ['x' => 0, 'y' => 100],
                    'data' => ['text' => 'Hello & <world>', 'media' => null, 'keyboard' => null],
                ],
            ],
            'edges' => [],
        ],
    ]);

    $route = BotRoute::factory()->for($bot)->create([
        'handler_schema' => [
            'blocks' => [
                ['type' => 'reply', 'params' => ['text' => 'Say <hi>', 'media' => null, 'keyboard' => null]],
            ],
        ],
    ]);

    runEscapeMigration();

    $flow->refresh();
    $route->refresh();
    $bot->refresh();

    $nodes = $flow->graph['nodes'];
    expect($nodes[0]['data']['text'])->toBe('Hi &lt;user&gt;! {{user.firstName}}');
    expect($nodes[1]['data']['text'])->toBe('Hello &amp; &lt;world&gt;');
    expect($route->handler_schema['blocks'][0]['params']['text'])->toBe('Say &lt;hi&gt;');
    expect($bot->text_format_version)->toBe(2);
});

test('leaves version-2 bots untouched', function () {
    $user = User::factory()->admin()->create();
    $bot = Bot::factory()->for($user, 'creator')->create(['text_format_version' => 2]);

    $flow = BotFlow::factory()->for($bot)->create([
        'graph' => [
            'nodes' => [
                [
                    'id' => 'n1', 'type' => 'ask', 'position' => ['x' => 0, 'y' => 0],
                    'data' => [
                        'mode' => 'text', 'stepName' => '', 'media' => null,
                        'validation' => [], 'keyboard' => null,
                        'text' => 'Already <b>formatted</b>',
                    ],
                ],
            ],
            'edges' => [],
        ],
    ]);

    runEscapeMigration();

    $flow->refresh();
    expect($flow->graph['nodes'][0]['data']['text'])->toBe('Already <b>formatted</b>');
});