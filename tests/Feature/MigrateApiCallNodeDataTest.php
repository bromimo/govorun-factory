<?php

use App\Models\Bot;
use App\Models\BotFlow;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Запустить миграцию данных api_call-нод (идемпотентно).
 */
function runApiCallNodeDataMigration(): void
{
    $migrationPath = database_path('migrations/2026_05_16_130000_migrate_api_call_node_data.php');
    $migration = require $migrationPath;
    $migration->up();
}

it('добавляет недостающие поля в api_call ноды', function () {
    $bot = Bot::factory()->create();
    $flow = BotFlow::factory()->for($bot)->create([
        'graph' => [
            'nodes' => [
                ['id' => '1', 'type' => 'api_call', 'data' => ['url' => 'https://x.com', 'method' => 'GET']],
                ['id' => '2', 'type' => 'reply', 'data' => ['text' => 'hi']],
            ],
            'edges' => [],
        ],
    ]);

    runApiCallNodeDataMigration();

    $api = $flow->fresh()->graph['nodes'][0];

    expect($api['data'])->toHaveKeys(['connection_id', 'method', 'path', 'headers', 'query', 'body_mode', 'body', 'response_mapping', 'on_error']);
    expect($api['data']['connection_id'])->toBeNull();
    expect($api['data']['response_mapping'])->toBe([]);
    expect($api['data']['on_error'])->toBe('stop_flow');
    expect($api['data'])->not->toHaveKey('url');
});
