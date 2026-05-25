<?php

use App\Models\Bot;
use App\Models\BotFlow;
use App\Models\BotRoute;
use App\Services\SchemaValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->bot = Bot::factory()->create([
        'messenger_config' => ['telegram' => ['token' => 'x']],
    ]);
});

function makeTextFlow(Bot $bot, string $text, string $type = 'ask'): BotFlow
{
    $data = $type === 'ask'
        ? ['mode' => 'text', 'stepName' => 'askStep', 'text' => $text, 'media' => null, 'validation' => [], 'keyboard' => null]
        : ['text' => $text, 'media' => null, 'keyboard' => null];

    return BotFlow::factory()->for($bot)->create([
        'graph' => [
            'nodes' => [
                ['id' => 'n1', 'type' => $type, 'data' => $data, 'position' => ['x' => 0, 'y' => 0]],
                ['id' => 'oc', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 100]],
            ],
            'edges' => [['source' => 'n1', 'target' => 'oc']],
        ],
    ]);
}

test('valid Telegram HTML in ask text passes validation', function () {
    makeTextFlow($this->bot, '<b>Привет</b>, как вас зовут?', 'ask');
    BotRoute::factory()->for($this->bot)->create([
        'handler_schema' => ['blocks' => [['type' => 'reply', 'params' => ['text' => 'ok', 'media' => null, 'keyboard' => null]]]],
    ]);

    $result = (new SchemaValidator($this->bot))->validate();
    expect($result->isValid())->toBeTrue();
});

test('script tag in ask text fails validation', function () {
    makeTextFlow($this->bot, '<script>alert(1)</script>вопрос', 'ask');
    BotRoute::factory()->for($this->bot)->create([
        'handler_schema' => ['blocks' => [['type' => 'reply', 'params' => ['text' => 'ok', 'media' => null, 'keyboard' => null]]]],
    ]);

    $result = (new SchemaValidator($this->bot))->validate();
    expect($result->isValid())->toBeFalse();
    expect(implode(' ', $result->errors))->toContain('text содержит неподдерживаемый Telegram HTML');
});

test('javascript href in reply text fails validation', function () {
    BotRoute::factory()->for($this->bot)->create([
        'handler_schema' => [
            'blocks' => [
                ['type' => 'reply', 'params' => ['text' => '<a href="javascript:x">click</a>', 'media' => null, 'keyboard' => null]],
            ],
        ],
    ]);
    BotFlow::factory()->for($this->bot)->create([
        'graph' => [
            'nodes' => [['id' => 'oc', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 0]]],
            'edges' => [],
        ],
    ]);

    $result = (new SchemaValidator($this->bot))->validate();
    expect($result->isValid())->toBeFalse();
    expect(implode(' ', $result->errors))->toContain('text содержит неподдерживаемый Telegram HTML');
});

test('valid Telegram HTML in reply text passes validation', function () {
    BotRoute::factory()->for($this->bot)->create([
        'handler_schema' => [
            'blocks' => [
                ['type' => 'reply', 'params' => [
                    'text' => '<b>bold</b> and <span class="tg-spoiler">secret</span>',
                    'media' => null, 'keyboard' => null,
                ]],
            ],
        ],
    ]);
    BotFlow::factory()->for($this->bot)->create([
        'graph' => [
            'nodes' => [['id' => 'oc', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 0]]],
            'edges' => [],
        ],
    ]);

    $result = (new SchemaValidator($this->bot))->validate();
    expect($result->isValid())->toBeTrue();
});
