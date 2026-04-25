<?php

use App\Models\Bot;
use App\Models\User;
use App\Models\BotRoute;
use Illuminate\Support\Facades\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\CodeGenerator\CodeGeneratorService;

uses(RefreshDatabase::class);

function generateBotToTempDir(Bot $bot): string
{
    $dir = sys_get_temp_dir().'/govorun-export-'.uniqid();
    File::ensureDirectoryExists($dir);
    (new CodeGeneratorService)->generate($bot, $dir);

    return $dir;
}

test('command-controller goes into Commands subdirectory', function () {
    $bot = Bot::factory()->for(User::factory()->admin(), 'creator')->create();
    BotRoute::factory()->for($bot)->create([
        'type' => 'command',
        'match' => '/start',
        'controller_name' => 'Start',
        'handler_type' => 'controller',
        'handler_schema' => ['blocks' => []],
    ]);

    $dir = generateBotToTempDir($bot);

    expect(File::exists("{$dir}/app/Controllers/Commands/StartController.php"))->toBeTrue();
    expect(File::get("{$dir}/app/Controllers/Commands/StartController.php"))
        ->toContain('namespace App\\Controllers\\Commands;');

    File::deleteDirectory($dir);
});

test('fallback-controller stays in root Controllers directory', function () {
    $bot = Bot::factory()->for(User::factory()->admin(), 'creator')->create();
    BotRoute::factory()->for($bot)->create([
        'type' => 'fallback',
        'controller_name' => 'Fallback',
        'handler_type' => 'controller',
        'handler_schema' => ['blocks' => []],
    ]);

    $dir = generateBotToTempDir($bot);

    expect(File::exists("{$dir}/app/Controllers/FallbackController.php"))->toBeTrue();
    expect(File::get("{$dir}/app/Controllers/FallbackController.php"))
        ->toContain('namespace App\\Controllers;')
        ->not->toContain('namespace App\\Controllers\\Fallback');

    File::deleteDirectory($dir);
});

test('phrase-group controller goes into Phrases subdirectory', function () {
    $bot = Bot::factory()->for(User::factory()->admin(), 'creator')->create();
    $parent = BotRoute::factory()->for($bot)->create([
        'type' => 'phrase',
        'match' => 'booking',
        'controller_name' => 'Booking',
        'handler_type' => 'controller',
    ]);
    BotRoute::factory()->for($bot)->create([
        'parent_id' => $parent->id,
        'type' => 'phrase',
        'match' => 'list',
        'controller_name' => 'list',
        'handler_type' => 'controller',
        'handler_schema' => ['blocks' => []],
    ]);

    $dir = generateBotToTempDir($bot);

    expect(File::exists("{$dir}/app/Controllers/Phrases/BookingController.php"))->toBeTrue();
    expect(File::get("{$dir}/app/Controllers/Phrases/BookingController.php"))
        ->toContain('namespace App\\Controllers\\Phrases;');

    File::deleteDirectory($dir);
});

test('flow-handler controller goes into subdirectory by route type', function () {
    $bot = Bot::factory()->for(User::factory()->admin(), 'creator')->create();
    $flow = $bot->flows()->create(['name' => 'TestFlow', 'graph' => ['nodes' => [], 'edges' => []]]);
    BotRoute::factory()->for($bot)->create([
        'type' => 'event',
        'match' => 'member_joined',
        'controller_name' => null,
        'handler_type' => 'flow',
        'flow_id' => $flow->id,
    ]);

    $dir = generateBotToTempDir($bot);

    expect(File::exists("{$dir}/app/Controllers/Events/TestFlowController.php"))->toBeTrue();
    expect(File::get("{$dir}/app/Controllers/Events/TestFlowController.php"))
        ->toContain('namespace App\\Controllers\\Events;');

    File::deleteDirectory($dir);
});
