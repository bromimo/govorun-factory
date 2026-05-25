<?php

use App\Models\Bot;
use App\Models\BotFlow;
use App\Models\BotConnection;
use App\Enums\ConnectionAuthType;
use App\Services\CodeGenerator\FlowGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('генерирует http-вызов с маппингом ответа', function () {
    $bot = Bot::factory()->create();
    $conn = BotConnection::factory()->for($bot)->create([
        'slug' => 'bitrix_prod',
        'auth_type' => ConnectionAuthType::Bearer->value,
    ]);
    $flow = BotFlow::factory()->for($bot)->create([
        'graph' => [
            'nodes' => [
                ['id' => 's', 'type' => 'start', 'data' => []],
                ['id' => 'ask1', 'type' => 'ask', 'data' => [
                    'mode' => 'text', 'stepName' => 'askName', 'text' => 'Введите имя',
                    'media' => null, 'validation' => [], 'keyboard' => null,
                ]],
                ['id' => 'a', 'type' => 'api_call', 'data' => [
                    'connection_id' => $conn->id,
                    'method' => 'POST',
                    'path' => '/crm.lead.add',
                    'headers' => [],
                    'query' => [],
                    'body_mode' => 'json',
                    'body' => '{"fields":{"NAME":"{{state.name}}"}}',
                    'response_mapping' => [
                        ['json_path' => 'result.id', 'state_key' => 'lead_id'],
                    ],
                    'on_error' => 'stop_flow',
                ]],
                ['id' => 'b', 'type' => 'on_complete', 'data' => []],
            ],
            'edges' => [
                ['source' => 's', 'target' => 'ask1'],
                ['source' => 'ask1', 'target' => 'a'],
                ['source' => 'a', 'target' => 'b'],
            ],
        ],
    ]);

    $code = (new FlowGenerator)->generate('TestApiFlow', $flow->graph ?? [], [], false);

    expect($code)->toContain("\$this->http()->connection('bitrix_prod')->post(");
    expect($code)->toContain("'/crm.lead.add'");
    expect($code)->toContain("data_get(\$response->json(), 'result.id')");
    expect($code)->toContain("\$this->state->set('lead_id'");
});

it('при on_error=branch генерирует goTo на target по handle', function () {
    $bot = Bot::factory()->create();
    $conn = BotConnection::factory()->for($bot)->create(['slug' => 'svc']);
    $flow = BotFlow::factory()->for($bot)->create([
        'graph' => [
            'nodes' => [
                ['id' => 's', 'type' => 'start', 'data' => []],
                ['id' => 'ask1', 'type' => 'ask', 'data' => [
                    'mode' => 'text', 'stepName' => 'askStep', 'text' => 'Введите данные',
                    'media' => null, 'validation' => [], 'keyboard' => null,
                ]],
                ['id' => 'a', 'type' => 'api_call', 'data' => [
                    'connection_id' => $conn->id, 'method' => 'GET', 'path' => '/x',
                    'headers' => [], 'query' => [], 'body_mode' => 'none', 'body' => null,
                    'response_mapping' => [], 'on_error' => 'branch',
                ]],
                ['id' => 'b', 'type' => 'reply', 'data' => ['text' => 'ok']],
                ['id' => 'c', 'type' => 'reply', 'data' => ['text' => 'fail']],
                ['id' => 'd', 'type' => 'on_complete', 'data' => []],
            ],
            'edges' => [
                ['source' => 's', 'target' => 'ask1'],
                ['source' => 'ask1', 'target' => 'a'],
                ['source' => 'a', 'target' => 'b'],
                ['source' => 'a', 'target' => 'c', 'sourceHandle' => 'on_error'],
                ['source' => 'b', 'target' => 'd'],
                ['source' => 'c', 'target' => 'd'],
            ],
        ],
    ]);

    $code = (new FlowGenerator)->generate('TestOnErrorFlow', $flow->graph ?? [], [], false);

    expect($code)->toMatch('/if\s*\(\s*\$response->failed\(\)\s*\)\s*\{[^}]*goTo\(\'\w+\'/');
});
