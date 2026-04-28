<?php

use App\Services\CodeGenerator\ControllerGenerator;

test('generates controller class from handler schema', function () {
    $schema = [
        'blocks' => [
            ['type' => 'reply', 'params' => ['text' => 'Hello!', 'media' => null, 'keyboard' => null]],
            ['type' => 'reply', 'params' => [
                'text' => 'Choose:',
                'media' => null,
                'keyboard' => ['type' => 'inline', 'buttons' => [
                    [
                        ['type' => 'action', 'label' => 'Option A', 'action' => 'a'],
                        ['type' => 'action', 'label' => 'Option B', 'action' => 'b'],
                    ],
                ]],
            ]],
        ],
    ];

    $generator = new ControllerGenerator;
    $result = $generator->generate('StartController', $schema, 'App\\Controllers\\Commands');

    expect($result)->toContain('namespace App\\Controllers\\Commands;');
    expect($result)->toContain('class StartController extends Controller');
    expect($result)->toContain("Message::make('Hello!')");
    expect($result)->toContain("Message::make('Choose:')");
    expect($result)->toContain("Button::make('Option A')->action('a')");
    expect($result)->toContain('use Govorun\Messaging\Message;');
    expect($result)->toContain('use Govorun\Messaging\Button;');
    expect($result)->toContain('use Govorun\Messaging\Keyboard;');
});

test('generates controller with save_state block', function () {
    $schema = [
        'blocks' => [
            ['type' => 'save_state', 'params' => ['key' => 'name', 'source' => 'message.text']],
        ],
    ];

    $generator = new ControllerGenerator;
    $result = $generator->generate('SaveController', $schema, 'App\\Controllers');

    expect($result)->toContain('namespace App\\Controllers;');
    expect($result)->toContain("\$this->state->set('name', \$this->message->text)");
});
