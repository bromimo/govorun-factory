<?php

use App\Services\CodeGenerator\ControllerGenerator;

test('generates controller class from handler schema', function () {
    $schema = [
        'blocks' => [
            ['type' => 'reply_text', 'params' => ['text' => 'Hello!']],
            ['type' => 'reply_keyboard', 'params' => [
                'text' => 'Choose:',
                'buttons' => [
                    ['label' => 'Option A', 'action' => 'a'],
                    ['label' => 'Option B', 'action' => 'b'],
                ],
            ]],
        ],
    ];

    $generator = new ControllerGenerator;
    $result = $generator->generate('StartController', $schema);

    expect($result)->toContain('class StartController extends Controller');
    expect($result)->toContain("\$this->reply('Hello!')");
    expect($result)->toContain("Message::make('Choose:')");
    expect($result)->toContain("'label' => 'Option A'");
});

test('generates controller with save_state block', function () {
    $schema = [
        'blocks' => [
            ['type' => 'save_state', 'params' => ['key' => 'name', 'source' => 'text']],
        ],
    ];

    $generator = new ControllerGenerator;
    $result = $generator->generate('SaveController', $schema);

    expect($result)->toContain("\$this->state->set('name', \$this->message->text)");
});
