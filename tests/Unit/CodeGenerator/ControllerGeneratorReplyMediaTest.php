<?php

use App\Services\CodeGenerator\ControllerGenerator;

test('controller renders reply with photo media and caption via Media::photo', function () {
    $generator = new ControllerGenerator;
    $schema = [
        'blocks' => [
            [
                'type' => 'reply',
                'params' => [
                    'text' => 'Hi, {{ name }}!',
                    'media' => ['type' => 'photo', 'url' => 'https://example.com/a.jpg'],
                    'keyboard' => null,
                ],
            ],
        ],
    ];

    $result = $generator->generate('TestController', $schema, 'App\\Controllers\\Commands');

    expect($result)->toContain('use Govorun\\Messaging\\Media;');
    expect($result)->toContain('namespace App\\Controllers\\Commands;');
    expect($result)->not->toContain('use Govorun\\Messaging\\Message;');
    expect($result)->toContain("Media::photo('https://example.com/a.jpg')");
    expect($result)->toContain("->caption('Hi, ' . \$this->state->get('name') . '!')");
});

test('controller renders reply with photo media without text', function () {
    $generator = new ControllerGenerator;
    $schema = [
        'blocks' => [
            [
                'type' => 'reply',
                'params' => [
                    'text' => null,
                    'media' => ['type' => 'photo', 'url' => 'https://example.com/b.jpg'],
                    'keyboard' => null,
                ],
            ],
        ],
    ];

    $result = $generator->generate('TestController', $schema, 'App\\Controllers\\Commands');

    expect($result)->toContain("Media::photo('https://example.com/b.jpg')");
    expect($result)->not->toContain('->caption');
});

test('controller renders reply with video media via Media::video', function () {
    $generator = new ControllerGenerator;
    $schema = [
        'blocks' => [
            [
                'type' => 'reply',
                'params' => [
                    'text' => null,
                    'media' => ['type' => 'video', 'url' => 'https://example.com/c.mp4'],
                    'keyboard' => null,
                ],
            ],
        ],
    ];

    $result = $generator->generate('TestController', $schema, 'App\\Controllers\\Commands');

    expect($result)->toContain("Media::video('https://example.com/c.mp4')");
    expect($result)->toContain('use Govorun\\Messaging\\Media;');
});
