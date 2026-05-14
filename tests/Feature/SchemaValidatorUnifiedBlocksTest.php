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

function makeFlow(Bot $bot, array $nodes, array $edges = []): BotFlow
{
    return BotFlow::factory()->for($bot)->create([
        'graph' => ['nodes' => $nodes, 'edges' => $edges],
    ]);
}

function makeRoute(Bot $bot, array $blocks = []): BotRoute
{
    return BotRoute::factory()->for($bot)->create([
        'handler_schema' => ['blocks' => $blocks],
    ]);
}

test('reply with text only is valid', function () {
    makeRoute($this->bot, [
        ['type' => 'reply', 'params' => ['text' => 'Hi', 'media' => null, 'keyboard' => null]],
    ]);
    makeFlow($this->bot, [
        ['id' => 'oc', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
    ]);

    $result = (new SchemaValidator($this->bot))->validate();
    expect($result->isValid())->toBeTrue();
});

test('reply with neither text nor media is invalid', function () {
    makeRoute($this->bot, [
        ['type' => 'reply', 'params' => ['text' => null, 'media' => null, 'keyboard' => null]],
    ]);
    makeFlow($this->bot, [
        ['id' => 'oc', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
    ]);

    $result = (new SchemaValidator($this->bot))->validate();
    expect($result->isValid())->toBeFalse();
});

test('ask in text mode requires no keyboard', function () {
    makeRoute($this->bot, [
        ['type' => 'reply', 'params' => ['text' => 'Hi', 'media' => null, 'keyboard' => null]],
    ]);
    makeFlow($this->bot, [
        ['id' => 'a', 'type' => 'ask', 'data' => [
            'mode' => 'text', 'stepName' => 'askName',
            'text' => 'Имя?', 'media' => null,
            'validation' => [], 'keyboard' => null,
        ], 'position' => ['x' => 0, 'y' => 0]],
        ['id' => 'oc', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 100]],
    ], [['source' => 'a', 'target' => 'oc']]);

    $result = (new SchemaValidator($this->bot))->validate();
    expect($result->isValid())->toBeTrue();
});

test('ask in callback mode requires non-empty keyboard', function () {
    makeRoute($this->bot, [
        ['type' => 'reply', 'params' => ['text' => 'Hi', 'media' => null, 'keyboard' => null]],
    ]);
    makeFlow($this->bot, [
        ['id' => 'a', 'type' => 'ask', 'data' => [
            'mode' => 'callback', 'stepName' => 'askColor',
            'text' => 'Цвет?', 'media' => null,
            'validation' => [], 'keyboard' => null,
        ], 'position' => ['x' => 0, 'y' => 0]],
        ['id' => 'oc', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 100]],
    ]);

    $result = (new SchemaValidator($this->bot))->validate();
    expect($result->isValid())->toBeFalse();
});

test('legacy ask_text type is rejected', function () {
    makeRoute($this->bot, [
        ['type' => 'reply', 'params' => ['text' => 'Hi', 'media' => null, 'keyboard' => null]],
    ]);
    makeFlow($this->bot, [
        ['id' => 'a', 'type' => 'ask_text', 'data' => ['text' => 'x'], 'position' => ['x' => 0, 'y' => 0]],
        ['id' => 'oc', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 100]],
    ]);

    $result = (new SchemaValidator($this->bot))->validate();
    expect($result->isValid())->toBeFalse();
    expect($result->errors)->toContain('Тип блока «ask_text» больше не поддерживается. Откройте flow в редакторе для миграции.');
});

test('legacy reply_media type is rejected', function () {
    makeRoute($this->bot, [
        ['type' => 'reply_media', 'params' => ['media_type' => 'photo', 'url' => 'x']],
    ]);
    makeFlow($this->bot, [
        ['id' => 'oc', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
    ]);

    $result = (new SchemaValidator($this->bot))->validate();
    expect($result->isValid())->toBeFalse();
});

test('ask with unknown media type is rejected', function () {
    makeRoute($this->bot, [
        ['type' => 'reply', 'params' => ['text' => 'Hi', 'media' => null, 'keyboard' => null]],
    ]);
    makeFlow($this->bot, [
        ['id' => 'a', 'type' => 'ask', 'data' => [
            'mode' => 'text', 'stepName' => 'askName',
            'text' => 'Имя?',
            'media' => ['type' => 'sticker', 'url' => 'x'],
            'validation' => [], 'keyboard' => null,
        ], 'position' => ['x' => 0, 'y' => 0]],
        ['id' => 'oc', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 100]],
    ]);

    $result = (new SchemaValidator($this->bot))->validate();
    expect($result->isValid())->toBeFalse();
});