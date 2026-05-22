<?php

use App\Models\Bot;
use App\Models\User;
use App\Models\BotFlow;
use App\Models\BotRoute;
use Illuminate\Support\Facades\File;
use App\Services\CodeGenerator\CodeGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function generateForStatusTest(Bot $bot): string
{
    $dir = sys_get_temp_dir().'/govorun-status-'.uniqid();
    File::ensureDirectoryExists($dir);
    (new CodeGeneratorService)->generate($bot, $dir);

    return $dir;
}

test('inactive routes are excluded from generated routes file', function () {
    $bot = Bot::factory()->for(User::factory()->admin(), 'creator')->create();

    BotRoute::factory()->for($bot)->create([
        'type' => 'command', 'match' => '/active', 'controller_name' => 'ActiveCmd',
        'handler_type' => 'controller', 'handler_schema' => ['blocks' => []],
        'status' => 'active',
    ]);
    BotRoute::factory()->for($bot)->create([
        'type' => 'command', 'match' => '/inactive', 'controller_name' => 'InactiveCmd',
        'handler_type' => 'controller', 'handler_schema' => ['blocks' => []],
        'status' => 'inactive',
    ]);
    BotRoute::factory()->for($bot)->create([
        'type' => 'command', 'match' => '/draft', 'controller_name' => 'DraftCmd',
        'handler_type' => 'controller', 'handler_schema' => ['blocks' => []],
        'status' => 'draft',
    ]);

    $dir = generateForStatusTest($bot);
    $routesCode = File::get("{$dir}/routes/messenger.php");

    expect($routesCode)->toContain('/active');
    expect($routesCode)->not->toContain('/inactive');
    expect($routesCode)->not->toContain('/draft');

    File::deleteDirectory($dir);
});

test('inactive flows are excluded from generated flow classes', function () {
    $bot = Bot::factory()->for(User::factory()->admin(), 'creator')->create();

    BotFlow::factory()->for($bot)->create([
        'name' => 'ActiveFlow',
        'graph' => ['nodes' => [['id' => 'start', 'type' => 'start']], 'edges' => []],
        'status' => 'active',
    ]);
    BotFlow::factory()->for($bot)->create([
        'name' => 'InactiveFlow',
        'graph' => ['nodes' => [['id' => 'start', 'type' => 'start']], 'edges' => []],
        'status' => 'inactive',
    ]);

    $dir = generateForStatusTest($bot);
    $files = collect(File::files("{$dir}/app/Flows"))->map(fn ($f) => $f->getFilename());

    expect($files)->toContain('ActiveFlow.php');
    expect($files)->not->toContain('InactiveFlow.php');

    File::deleteDirectory($dir);
});