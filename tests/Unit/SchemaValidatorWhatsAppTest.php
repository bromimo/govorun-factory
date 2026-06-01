<?php

use App\Models\Bot;
use App\Services\SchemaValidator;

/** Построить flow-нод с inline-клавиатурой из переданных кнопок (1 ряд). */
function waFlow(array $buttons): array
{
    return [
        'nodes' => [
            ['id' => 'n1', 'type' => 'ask', 'data' => [
                'mode' => 'callback',
                'text' => 'Выберите',
                'keyboard' => ['type' => 'inline', 'buttons' => [$buttons]],
            ]],
            ['id' => 'oc', 'type' => 'on_complete', 'data' => []],
        ],
        'edges' => [],
    ];
}

function waBot(bool $enabled): Bot
{
    return new Bot(['messenger_config' => ['whatsapp' => ['enabled' => $enabled]]]);
}

test('whatsapp keyboard with more than 10 buttons is invalid', function () {
    $buttons = [];
    for ($i = 0; $i < 11; $i++) {
        $buttons[] = ['label' => "B{$i}", 'type' => 'action', 'action' => "a{$i}"];
    }
    $validator = new SchemaValidator(waBot(true));

    $result = $validator->validateKeyboardForTest(['type' => 'inline', 'buttons' => [$buttons]], 'нода n1');

    expect($result->isValid())->toBeFalse();
});

test('whatsapp keyboard with url button is invalid', function () {
    $validator = new SchemaValidator(waBot(true));

    $result = $validator->validateKeyboardForTest([
        'type' => 'inline',
        'buttons' => [[['label' => 'Сайт', 'type' => 'url', 'url' => 'https://e.com']]],
    ], 'нода n1');

    expect($result->isValid())->toBeFalse();
});

test('whatsapp keyboard with three short action buttons is valid', function () {
    $validator = new SchemaValidator(waBot(true));

    $result = $validator->validateKeyboardForTest([
        'type' => 'inline',
        'buttons' => [[
            ['label' => 'Да', 'type' => 'action', 'action' => 'yes'],
            ['label' => 'Нет', 'type' => 'action', 'action' => 'no'],
        ]],
    ], 'нода n1');

    expect($result->isValid())->toBeTrue();
});

test('keyboard rules skipped when whatsapp disabled', function () {
    $validator = new SchemaValidator(waBot(false));

    $result = $validator->validateKeyboardForTest([
        'type' => 'inline',
        'buttons' => [[['label' => 'Сайт', 'type' => 'url', 'url' => 'https://e.com']]],
    ], 'нода n1');

    expect($result->isValid())->toBeTrue();
});
