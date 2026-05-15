<?php

use App\Services\CodeGenerator\ControllerGenerator;

test('renderBlock reply with text only emits send Message::make', function () {
    $gen = new ControllerGenerator;
    $code = $gen->renderBlock('reply', [
        'text' => 'Hi', 'media' => null, 'keyboard' => null,
    ]);

    expect($code)->toContain('$this->send(');
    expect($code)->toContain("Message::make('Hi')");
});

test('renderBlock reply with media only emits Media::photo', function () {
    $gen = new ControllerGenerator;
    $code = $gen->renderBlock('reply', [
        'text' => null,
        'media' => ['type' => 'photo', 'url' => 'https://x/y.jpg'],
        'keyboard' => null,
    ]);

    expect($code)->toContain("Media::photo('https://x/y.jpg')");
    expect($code)->not->toContain('caption(');
});

test('renderBlock reply with media+text emits caption', function () {
    $gen = new ControllerGenerator;
    $code = $gen->renderBlock('reply', [
        'text' => 'Подпись',
        'media' => ['type' => 'video', 'url' => 'https://x/y.mp4'],
        'keyboard' => null,
    ]);

    expect($code)->toContain("Media::video('https://x/y.mp4')");
    expect($code)->toContain("->caption('Подпись')");
});

test('renderBlock reply with keyboard emits ->keyboard chain', function () {
    $gen = new ControllerGenerator;
    $code = $gen->renderBlock('reply', [
        'text' => 'Pick',
        'media' => null,
        'keyboard' => ['type' => 'inline', 'buttons' => [
            [['type' => 'action', 'label' => 'A', 'action' => 'a']],
        ]],
    ]);

    expect($code)->toContain("Message::make('Pick')");
    expect($code)->toContain('->keyboard(');
    expect($code)->toContain("Button::make('A')");
});

test('renderBlock reply empty (no text and no media) emits comment', function () {
    $gen = new ControllerGenerator;
    $code = $gen->renderBlock('reply', [
        'text' => null, 'media' => null, 'keyboard' => null,
    ]);

    expect($code)->toContain('// Empty reply block');
});

test('renderBlock reply with reply keyboard emits Keyboard::reply()', function () {
    $gen = new ControllerGenerator;
    $code = $gen->renderBlock('reply', [
        'text' => 'Выберите',
        'media' => null,
        'keyboard' => [
            'type' => 'reply',
            'resize' => false,
            'oneTime' => false,
            'buttons' => [[['type' => 'text', 'label' => 'Да']]],
        ],
    ]);

    expect($code)->toContain('Keyboard::reply()');
    expect($code)->not->toContain('Keyboard::make()');
    expect($code)->toContain("Button::make('Да')");
});

test('renderBlock reply with reply keyboard and resize flag emits ->resize()', function () {
    $gen = new ControllerGenerator;
    $code = $gen->renderBlock('reply', [
        'text' => 'Выберите',
        'media' => null,
        'keyboard' => [
            'type' => 'reply',
            'resize' => true,
            'oneTime' => false,
            'buttons' => [[['type' => 'text', 'label' => 'Да']]],
        ],
    ]);

    expect($code)->toContain('Keyboard::reply()');
    expect($code)->toContain('->resize()');
    expect($code)->not->toContain('->oneTime()');
});

test('renderBlock reply with reply keyboard and oneTime flag emits ->oneTime() and contact button', function () {
    $gen = new ControllerGenerator;
    $code = $gen->renderBlock('reply', [
        'text' => 'Выберите',
        'media' => null,
        'keyboard' => [
            'type' => 'reply',
            'resize' => false,
            'oneTime' => true,
            'buttons' => [[['type' => 'contact', 'label' => 'Контакт']]],
        ],
    ]);

    expect($code)->toContain('Keyboard::reply()');
    expect($code)->toContain('->oneTime()');
    expect($code)->toContain('->requestContact()');
});