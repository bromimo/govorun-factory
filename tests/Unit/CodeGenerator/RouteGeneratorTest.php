<?php

use App\Models\Bot;
use App\Models\BotRoute;
use App\Models\User;
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
