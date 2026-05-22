<?php

use App\Models\Bot;
use App\Models\User;
use App\Models\BotFlow;
use App\Models\BotRoute;
use Illuminate\Support\Facades\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\CodeGenerator\CodeGeneratorService;

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
    BotFlow::factory()->for($bot)->create([
        'name' => 'DraftFlow',
        'graph' => ['nodes' => [['id' => 'start', 'type' => 'start']], 'edges' => []],
        'status' => 'draft',
    ]);

    $dir = generateForStatusTest($bot);
    $files = collect(File::files("{$dir}/app/Flows"))->map(fn ($f) => $f->getFilename());

    expect($files)->toContain('ActiveFlow.php');
    expect($files)->not->toContain('InactiveFlow.php');
    expect($files)->not->toContain('DraftFlow.php');

    File::deleteDirectory($dir);
});

test('inactive child routes are excluded from generated routes file', function () {
    $bot = Bot::factory()->for(User::factory()->admin(), 'creator')->create();

    $parent = BotRoute::factory()->for($bot)->create([
        'type' => 'phrase', 'match' => 'menu', 'controller_name' => 'Menu',
        'handler_type' => 'controller', 'handler_schema' => ['blocks' => []],
        'status' => 'active',
    ]);
    BotRoute::factory()->for($bot)->create([
        'parent_id' => $parent->id, 'type' => 'phrase', 'match' => 'active-child',
        'controller_name' => 'ActiveChild',
        'handler_type' => 'controller', 'handler_schema' => ['blocks' => []],
        'status' => 'active',
    ]);
    BotRoute::factory()->for($bot)->create([
        'parent_id' => $parent->id, 'type' => 'phrase', 'match' => 'inactive-child',
        'controller_name' => 'InactiveChild',
        'handler_type' => 'controller', 'handler_schema' => ['blocks' => []],
        'status' => 'inactive',
    ]);

    $dir = generateForStatusTest($bot);
    $routesCode = File::get("{$dir}/routes/messenger.php");

    expect($routesCode)->toContain('active-child');
    expect($routesCode)->not->toContain('inactive-child');

    File::deleteDirectory($dir);
});
