<?php

use App\Models\Bot;
use App\Models\BotFlow;
use App\Models\BotMedia;
use App\Models\BotRoute;
use App\Services\ExportService;
use App\Services\SchemaValidator;
use App\Services\CodeGenerator\CodeGeneratorService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('export zip includes media file referenced by flow node', function () {
    Storage::fake('local');

    $bot = Bot::factory()->create([
        'name'             => 'Test Bot',
        'messenger_config' => ['telegram' => ['token' => 'TEST']],
    ]);
    BotRoute::factory()->for($bot)->create();

    $media = BotMedia::factory()->create([
        'bot_id'   => $bot->id,
        'filename' => 'abc123.jpg',
        'type'     => 'photo',
    ]);
    Storage::disk('local')->put("media/{$bot->id}/abc123.jpg", 'fake-image-content');

    BotFlow::factory()->create([
        'bot_id' => $bot->id,
        'name'   => 'TestFlow',
        'graph'  => [
            'nodes' => [
                [
                    'id'       => 'n1',
                    'type'     => 'reply',
                    'position' => ['x' => 0, 'y' => 0],
                    'data'     => [
                        'text'     => '',
                        'media'    => ['type' => 'photo', 'media_id' => $media->id],
                        'keyboard' => null,
                    ],
                ],
                [
                    'id'       => 'oc',
                    'type'     => 'on_complete',
                    'data'     => [],
                    'position' => ['x' => 0, 'y' => 100],
                ],
            ],
            'edges' => [],
        ],
    ]);

    $service = new ExportService(new SchemaValidator($bot), new CodeGeneratorService);
    $result  = $service->export($bot);

    expect($result['success'])->toBeTrue();

    $zip = new ZipArchive;
    $zip->open($result['path']);
    $found = false;

    for ($i = 0; $i < $zip->numFiles; $i++) {
        if (str_ends_with($zip->getNameIndex($i), 'resources/media/abc123.jpg')) {
            $found = true;
        }
    }

    $zip->close();
    expect($found)->toBeTrue();

    @unlink($result['path']);
});
