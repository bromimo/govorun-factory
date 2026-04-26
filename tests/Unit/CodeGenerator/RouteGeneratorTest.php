<?php

use App\Models\Bot;
use App\Models\User;
use App\Models\BotRoute;
use App\Services\CodeGenerator\RouteGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('generates phrase route without alias call when no aliases', function () {
    $bot = Bot::factory()->for(User::factory()->admin(), 'creator')->create();
    BotRoute::factory()->for($bot)->create([
        'type' => 'phrase',
        'match' => 'запись',
        'aliases' => null,
    ]);

    $generator = new RouteGenerator;
    $result = $generator->generate($bot);

    expect($result)->toContain("Route::phrase('запись',");
    expect($result)->not->toContain('::class)->alias(');
});

test('generates phrase route with alias call when aliases present', function () {
    $bot = Bot::factory()->for(User::factory()->admin(), 'creator')->create();
    BotRoute::factory()->for($bot)->create([
        'type' => 'phrase',
        'match' => 'запись',
        'aliases' => ['записаться', 'записать'],
    ]);

    $generator = new RouteGenerator;
    $result = $generator->generate($bot);

    expect($result)->toContain("Route::phrase('запись',");
    expect($result)->toContain("->alias(['записаться', 'записать'])");
});

test('does not generate alias for non-phrase routes', function () {
    $bot = Bot::factory()->for(User::factory()->admin(), 'creator')->create();
    BotRoute::factory()->for($bot)->create([
        'type' => 'command',
        'match' => '/start',
        'aliases' => null,
    ]);

    $generator = new RouteGenerator;
    $result = $generator->generate($bot);

    expect($result)->toContain("Route::command('/start',");
    expect($result)->not->toContain('::class)->alias(');
});

test('imports controllers from subdirectories matching route types', function () {
    $bot = Bot::factory()->for(User::factory()->admin(), 'creator')->create();
    BotRoute::factory()->for($bot)->create([
        'type' => 'command',
        'match' => '/start',
        'controller_name' => 'Start',
        'handler_type' => 'controller',
    ]);
    BotRoute::factory()->for($bot)->create([
        'type' => 'fallback',
        'controller_name' => 'Fallback',
        'handler_type' => 'controller',
    ]);

    $generator = new RouteGenerator;
    $result = $generator->generate($bot);

    expect($result)->toContain('use App\\Controllers\\Commands\\StartController;');
    expect($result)->toContain('use App\\Controllers\\FallbackController;');
});

test('aliases imports when controller short names collide across subdirectories', function () {
    $bot = Bot::factory()->for(User::factory()->admin(), 'creator')->create();
    BotRoute::factory()->for($bot)->create([
        'type' => 'command',
        'match' => '/start',
        'controller_name' => 'Start',
        'handler_type' => 'controller',
    ]);
    BotRoute::factory()->for($bot)->create([
        'type' => 'phrase',
        'match' => 'старт',
        'controller_name' => 'Start',
        'handler_type' => 'controller',
    ]);

    $generator = new RouteGenerator;
    $result = $generator->generate($bot);

    expect($result)->toContain('use App\\Controllers\\Commands\\StartController as CommandsStartController;');
    expect($result)->toContain('use App\\Controllers\\Phrases\\StartController as PhrasesStartController;');
    expect($result)->toContain('CommandsStartController::class');
    expect($result)->toContain('PhrasesStartController::class');
    expect($result)->not->toContain('use App\\Controllers\\Commands\\StartController;');
});

test('aliases imports when subdir controller named Fallback collides with fallback route', function () {
    $bot = Bot::factory()->for(User::factory()->admin(), 'creator')->create();
    BotRoute::factory()->for($bot)->create([
        'type' => 'phrase',
        'match' => 'fallback',
        'controller_name' => 'Fallback',
        'handler_type' => 'controller',
    ]);
    BotRoute::factory()->for($bot)->create([
        'type' => 'fallback',
        'controller_name' => null,
        'handler_type' => 'controller',
    ]);

    $generator = new RouteGenerator;
    $result = $generator->generate($bot);

    expect($result)->toContain('use App\\Controllers\\Phrases\\FallbackController as PhrasesFallbackController;');
    expect($result)->toContain('use App\\Controllers\\FallbackController as ControllersFallbackController;');
    expect($result)->toContain('PhrasesFallbackController::class');
    expect($result)->toContain('Route::fallback(ControllersFallbackController::class);');
});

test('ignores controller_name on fallback routes (always uses FallbackController)', function () {
    $bot = Bot::factory()->for(User::factory()->admin(), 'creator')->create();
    BotRoute::factory()->for($bot)->create([
        'type' => 'fallback',
        'controller_name' => 'CustomName',
        'handler_type' => 'controller',
    ]);

    $generator = new RouteGenerator;
    $result = $generator->generate($bot);

    expect($result)->toContain('use App\\Controllers\\FallbackController;');
    expect($result)->toContain('Route::fallback(FallbackController::class);');
    expect($result)->not->toContain('CustomName');
});

test('does not alias when no collisions exist', function () {
    $bot = Bot::factory()->for(User::factory()->admin(), 'creator')->create();
    BotRoute::factory()->for($bot)->create([
        'type' => 'command',
        'match' => '/start',
        'controller_name' => 'Start',
        'handler_type' => 'controller',
    ]);
    BotRoute::factory()->for($bot)->create([
        'type' => 'phrase',
        'match' => 'привет',
        'controller_name' => 'Greet',
        'handler_type' => 'controller',
    ]);

    $generator = new RouteGenerator;
    $result = $generator->generate($bot);

    expect($result)->toContain('use App\\Controllers\\Commands\\StartController;');
    expect($result)->toContain('use App\\Controllers\\Phrases\\GreetController;');
    expect($result)->not->toContain(' as ');
});

test('generated routes file passes php -l after collision aliasing', function () {
    $bot = Bot::factory()->for(User::factory()->admin(), 'creator')->create();
    BotRoute::factory()->for($bot)->create([
        'type' => 'command',
        'match' => '/start',
        'controller_name' => 'Start',
        'handler_type' => 'controller',
    ]);
    BotRoute::factory()->for($bot)->create([
        'type' => 'phrase',
        'match' => 'старт',
        'controller_name' => 'Start',
        'handler_type' => 'controller',
    ]);
    BotRoute::factory()->for($bot)->create([
        'type' => 'fallback',
        'controller_name' => 'Start',
        'handler_type' => 'controller',
    ]);

    $generator = new RouteGenerator;
    $result = $generator->generate($bot);

    $tmp = tempnam(sys_get_temp_dir(), 'routes_').'.php';
    file_put_contents($tmp, $result);
    exec('php -l '.escapeshellarg($tmp).' 2>&1', $out, $exit);
    unlink($tmp);

    expect($exit)->toBe(0, 'php -l failed: '.implode("\n", $out)."\nresult:\n{$result}");
});
