<?php

use App\Models\Bot;
use App\Models\BotMedia;
use App\Services\CodeGenerator\FlowGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('generates local path when media_id is set', function () {
    $bot = Bot::factory()->create();
    $media = BotMedia::factory()->create([
        'bot_id' => $bot->id,
        'type' => 'photo',
        'filename' => 'abc123.jpg',
    ]);

    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            [
                'id' => 'ask1',
                'type' => 'ask',
                'position' => ['x' => 0, 'y' => 100],
                'data' => ['mode' => 'text', 'stepName' => '', 'text' => 'Question?', 'media' => null, 'validation' => [], 'keyboard' => null],
            ],
            [
                'id' => 'n1',
                'type' => 'reply',
                'position' => ['x' => 0, 'y' => 200],
                'data' => [
                    'text' => '',
                    'media' => ['type' => 'photo', 'media_id' => $media->id],
                    'keyboard' => null,
                ],
            ],
            ['id' => 'done', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 300]],
        ],
        'edges' => [
            ['source' => 'start', 'target' => 'ask1'],
            ['source' => 'ask1', 'target' => 'n1'],
            ['source' => 'n1', 'target' => 'done'],
        ],
    ];

    $result = (new FlowGenerator)->generate(
        'TestFlow', $graph, [], false, [], [$media->id => 'abc123.jpg']
    );

    expect($result)->toContain("dirname(__DIR__, 2) . '/resources/media/abc123.jpg'");
    expect($result)->not->toContain('https://');
});

test('generates url string when media url is set', function () {
    $graph = [
        'nodes' => [
            ['id' => 'start', 'type' => 'start', 'data' => [], 'position' => ['x' => 0, 'y' => 0]],
            [
                'id' => 'ask1',
                'type' => 'ask',
                'position' => ['x' => 0, 'y' => 100],
                'data' => ['mode' => 'text', 'stepName' => '', 'text' => 'Question?', 'media' => null, 'validation' => [], 'keyboard' => null],
            ],
            [
                'id' => 'n1',
                'type' => 'reply',
                'position' => ['x' => 0, 'y' => 200],
                'data' => [
                    'text' => '',
                    'media' => ['type' => 'photo', 'url' => 'https://example.com/img.jpg'],
                    'keyboard' => null,
                ],
            ],
            ['id' => 'done', 'type' => 'on_complete', 'data' => [], 'position' => ['x' => 0, 'y' => 300]],
        ],
        'edges' => [
            ['source' => 'start', 'target' => 'ask1'],
            ['source' => 'ask1', 'target' => 'n1'],
            ['source' => 'n1', 'target' => 'done'],
        ],
    ];

    $result = (new FlowGenerator)->generate('TestFlow', $graph, [], false);

    expect($result)->toContain("'https://example.com/img.jpg'");
});
