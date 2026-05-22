<?php

use App\Models\Bot;
use App\Models\User;
use App\Models\BotRoute;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->bot = Bot::factory()->for($this->admin, 'creator')->create([
        'messenger_config' => ['telegram' => ['token' => 'x']],
    ]);
});

test('newly created route has status draft', function () {
    $this->actingAs($this->admin)->post("/bots/{$this->bot->id}/routes", [
        'type' => 'command',
        'match' => '/start',
        'controller_name' => 'Start',
        'handler_type' => 'controller',
        'handler_schema' => ['blocks' => [['type' => 'reply', 'params' => ['text' => 'hi']]]],
    ])->assertRedirect();

    expect(BotRoute::where('bot_id', $this->bot->id)->first()->status->value)->toBe('draft');
});

test('PATCH status transitions draft to active when valid', function () {
    $route = BotRoute::factory()->for($this->bot)->create([
        'type' => 'command', 'match' => '/start', 'controller_name' => 'Start',
        'handler_type' => 'controller',
        'handler_schema' => ['blocks' => [['type' => 'reply', 'params' => ['text' => 'hi']]]],
        'status' => 'draft',
    ]);

    $this->actingAs($this->admin)
        ->patchJson("/bots/{$this->bot->id}/routes/{$route->id}/status", ['status' => 'active'])
        ->assertOk()
        ->assertJsonPath('status', 'active');

    expect($route->fresh()->status->value)->toBe('active');
});

test('PATCH status returns 422 when validation fails', function () {
    $route = BotRoute::factory()->for($this->bot)->create([
        'type' => 'command', 'match' => '/start', 'controller_name' => 'Start',
        'handler_type' => 'controller',
        'handler_schema' => ['blocks' => []],
        'status' => 'draft',
    ]);

    $this->actingAs($this->admin)
        ->patchJson("/bots/{$this->bot->id}/routes/{$route->id}/status", ['status' => 'active'])
        ->assertStatus(422)
        ->assertJsonStructure(['errors']);

    expect($route->fresh()->status->value)->toBe('draft');
});

test('parent going inactive cascades active children to inactive', function () {
    $parent = BotRoute::factory()->for($this->bot)->create([
        'type' => 'phrase', 'match' => 'menu', 'controller_name' => 'Menu',
        'handler_type' => 'controller', 'status' => 'active',
    ]);
    $child1 = BotRoute::factory()->for($this->bot)->create([
        'parent_id' => $parent->id, 'type' => 'phrase', 'match' => 'a',
        'handler_type' => 'controller',
        'handler_schema' => ['blocks' => [['type' => 'reply', 'params' => ['text' => 'a']]]],
        'status' => 'active',
    ]);
    $child2 = BotRoute::factory()->for($this->bot)->create([
        'parent_id' => $parent->id, 'type' => 'phrase', 'match' => 'b',
        'handler_type' => 'controller',
        'handler_schema' => ['blocks' => [['type' => 'reply', 'params' => ['text' => 'b']]]],
        'status' => 'draft',
    ]);

    $this->actingAs($this->admin)
        ->patchJson("/bots/{$this->bot->id}/routes/{$parent->id}/status", ['status' => 'inactive'])
        ->assertOk();

    expect($child1->fresh()->status->value)->toBe('inactive');
    expect($child2->fresh()->status->value)->toBe('draft');
});

test('activating child blocked when parent not active', function () {
    $parent = BotRoute::factory()->for($this->bot)->create([
        'type' => 'phrase', 'match' => 'menu', 'controller_name' => 'Menu',
        'handler_type' => 'controller', 'status' => 'draft',
    ]);
    $child = BotRoute::factory()->for($this->bot)->create([
        'parent_id' => $parent->id, 'type' => 'phrase', 'match' => 'a',
        'handler_type' => 'controller',
        'handler_schema' => ['blocks' => [['type' => 'reply', 'params' => ['text' => 'a']]]],
        'status' => 'draft',
    ]);

    $this->actingAs($this->admin)
        ->patchJson("/bots/{$this->bot->id}/routes/{$child->id}/status", ['status' => 'active'])
        ->assertStatus(422)
        ->assertJsonPath('errors.0', fn ($v) => str_contains($v, 'родительск'));

    expect($child->fresh()->status->value)->toBe('draft');
});

test('PUT on active route with empty handler auto-drops to draft', function () {
    $route = BotRoute::factory()->for($this->bot)->create([
        'type' => 'command', 'match' => '/start', 'controller_name' => 'Start',
        'handler_type' => 'controller',
        'handler_schema' => ['blocks' => [['type' => 'reply', 'params' => ['text' => 'ok']]]],
        'status' => 'active',
    ]);

    $this->actingAs($this->admin)->put("/bots/{$this->bot->id}/routes/{$route->id}", [
        'type' => 'command', 'match' => '/start',
        'controller_name' => 'Start',
        'handler_type' => 'controller',
        'handler_schema' => ['blocks' => []],
    ])->assertRedirect();

    expect($route->fresh()->status->value)->toBe('draft');
});

test('PUT on inactive route with empty handler auto-drops to draft', function () {
    $route = BotRoute::factory()->for($this->bot)->create([
        'type' => 'command', 'match' => '/start', 'controller_name' => 'Start',
        'handler_type' => 'controller',
        'handler_schema' => ['blocks' => [['type' => 'reply', 'params' => ['text' => 'ok']]]],
        'status' => 'inactive',
    ]);

    $this->actingAs($this->admin)->put("/bots/{$this->bot->id}/routes/{$route->id}", [
        'type' => 'command', 'match' => '/start',
        'controller_name' => 'Start',
        'handler_type' => 'controller',
        'handler_schema' => ['blocks' => []],
    ])->assertRedirect();

    expect($route->fresh()->status->value)->toBe('draft');
});
