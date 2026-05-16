<?php

use App\Models\Bot;
use App\Models\BotFlow;
use App\Models\BotRoute;
use App\Models\BotConnection;
use App\Services\SchemaValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->bot = Bot::factory()->create(['messenger_config' => ['telegram' => ['token' => 't']]]);
    BotRoute::factory()->for($this->bot)->create();
});

function makeFlowWith(Bot $bot, array $apiData): BotFlow
{
    return BotFlow::factory()->for($bot)->create([
        'graph' => [
            'nodes' => [
                ['id' => 'a', 'type' => 'api_call', 'data' => $apiData],
                ['id' => 'b', 'type' => 'on_complete', 'data' => []],
            ],
            'edges' => [['source' => 'a', 'target' => 'b']],
        ],
    ]);
}

it('требует connection_id', function () {
    makeFlowWith($this->bot, [
        'connection_id' => null, 'method' => 'GET', 'path' => '/x',
        'response_mapping' => [], 'on_error' => 'stop_flow',
    ]);

    $errors = (new SchemaValidator($this->bot))->validate()->errors;

    expect(collect($errors)->some(fn ($e) => str_contains($e, 'подключение')))->toBeTrue();
});

it('требует непустой path', function () {
    $conn = BotConnection::factory()->for($this->bot)->create();
    makeFlowWith($this->bot, [
        'connection_id' => $conn->id, 'method' => 'GET', 'path' => '',
        'response_mapping' => [], 'on_error' => 'stop_flow',
    ]);

    $errors = (new SchemaValidator($this->bot))->validate()->errors;

    expect(collect($errors)->some(fn ($e) => str_contains($e, 'путь')))->toBeTrue();
});

it('запрещает дубликаты state_key в response_mapping', function () {
    $conn = BotConnection::factory()->for($this->bot)->create();
    makeFlowWith($this->bot, [
        'connection_id' => $conn->id, 'method' => 'GET', 'path' => '/x',
        'response_mapping' => [
            ['json_path' => 'a', 'state_key' => 'x'],
            ['json_path' => 'b', 'state_key' => 'x'],
        ],
        'on_error' => 'stop_flow',
    ]);

    $errors = (new SchemaValidator($this->bot))->validate()->errors;

    expect(collect($errors)->some(fn ($e) => str_contains($e, 'x')))->toBeTrue();
});

it('требует on_error edge при on_error=branch', function () {
    $conn = BotConnection::factory()->for($this->bot)->create();
    BotFlow::factory()->for($this->bot)->create([
        'graph' => [
            'nodes' => [
                ['id' => 'a', 'type' => 'api_call', 'data' => [
                    'connection_id' => $conn->id, 'method' => 'GET', 'path' => '/x',
                    'response_mapping' => [], 'on_error' => 'branch',
                ]],
                ['id' => 'b', 'type' => 'on_complete', 'data' => []],
            ],
            'edges' => [['source' => 'a', 'target' => 'b']],
        ],
    ]);

    $errors = (new SchemaValidator($this->bot))->validate()->errors;

    expect(collect($errors)->some(fn ($e) => str_contains($e, 'on_error')))->toBeTrue();
});
