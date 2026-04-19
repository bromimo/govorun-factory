<?php

use App\Services\CodeGenerator\ControllerGenerator;

test('controller renders reply_media type=photo via Media::photo with caption', function () {
    $generator = new ControllerGenerator;
    $schema = [
        'blocks' => [
            [
                'type' => 'reply_media',
                'params' => [
                    'media_type' => 'photo',
                    'url' => 'https://example.com/a.jpg',
                    'caption' => 'Hi, {{ name }}!',
                ],
            ],
        ],
    ];

    $result = $generator->generate('TestController', $schema);

    expect($result)->toContain("use Govorun\\Messaging\\Media;");
    expect($result)->toContain("        \$this->send(\n            Media::photo('https://example.com/a.jpg')\n                ->caption('Hi, ' . \$this->state->get('name') . '!')\n        );");
});

test('controller renders reply_media type=photo without caption', function () {
    $generator = new ControllerGenerator;
    $schema = [
        'blocks' => [
            [
                'type' => 'reply_media',
                'params' => [
                    'media_type' => 'photo',
                    'url' => 'https://example.com/b.jpg',
                    'caption' => '',
                ],
            ],
        ],
    ];

    $result = $generator->generate('TestController', $schema);

    expect($result)->toContain("\$this->send(Media::photo('https://example.com/b.jpg'));");
    expect($result)->not->toContain('->caption');
});

test('controller renders reply_media non-photo as unsupported comment', function () {
    $generator = new ControllerGenerator;
    $schema = [
        'blocks' => [
            [
                'type' => 'reply_media',
                'params' => [
                    'media_type' => 'video',
                    'url' => 'https://example.com/c.mp4',
                ],
            ],
        ],
    ];

    $result = $generator->generate('TestController', $schema);

    expect($result)->toContain('// Unsupported media type: video');
});
