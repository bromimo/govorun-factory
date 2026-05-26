<?php

use App\Models\Bot;
use App\Models\User;
use App\Models\BotFlow;
use App\Models\BotRoute;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->bot = Bot::factory()->for($this->admin, 'creator')->create([
        'messenger_config' => ['telegram' => ['token' => 'x']],
    ]);
});

test('newly created flow has status draft', function () {
    $this->actingAs($this->admin)->post("/bots/{$this->bot->id}/flows", [
        'name' => 'Hello',
    ])->assertRedirect();

    expect(BotFlow::where('bot_id', $this->bot->id)->first()->status->value)->toBe('draft');
});

test('PATCH status transitions flow draft to active when valid', function () {
    $flow = BotFlow::factory()->for($this->bot)->create([
        'graph' => [
            'nodes' => [
                ['id' => 'start', 'type' => 'start'],
                ['id' => 'oc_1', 'type' => 'on_complete'],
            ],
            'edges' => [],
        ],
        'status' => 'draft',
    ]);

    $this->actingAs($this->admin)
        ->patchJson("/bots/{$this->bot->id}/flows/{$flow->id}/status", ['status' => 'active'])
        ->assertOk()
        ->assertJsonPath('status', 'active');

    expect($flow->fresh()->status->value)->toBe('active');
});

test('PATCH status returns 422 when flow has no on_complete', function () {
    $flow = BotFlow::factory()->for($this->bot)->create([
        'graph' => [
            'nodes' => [['id' => 'start', 'type' => 'start']],
            'edges' => [],
        ],
        'status' => 'draft',
    ]);

    $this->actingAs($this->admin)
        ->patchJson("/bots/{$this->bot->id}/flows/{$flow->id}/status", ['status' => 'active'])
        ->assertStatus(422);

    expect($flow->fresh()->status->value)->toBe('draft');
});

test('impact endpoint lists referencing routes', function () {
    $flow = BotFlow::factory()->for($this->bot)->create(['status' => 'active']);
    $route = BotRoute::factory()->for($this->bot)->create([
        'type' => 'command', 'match' => '/start',
        'handler_type' => 'flow', 'flow_id' => $flow->id,
        'status' => 'active',
    ]);

    $this->actingAs($this->admin)
        ->getJson("/bots/{$this->bot->id}/flows/{$flow->id}/status/impact?target_status=inactive")
        ->assertOk()
        ->assertJsonPath('affected_routes.0.id', $route->id);
});

test('flow going inactive drops referencing routes to draft and nullifies flow_id', function () {
    $flow = BotFlow::factory()->for($this->bot)->create([
        'graph' => [
            'nodes' => [['id' => 'start', 'type' => 'start'], ['id' => 'oc', 'type' => 'on_complete']],
            'edges' => [],
        ],
        'status' => 'active',
    ]);
    $route = BotRoute::factory()->for($this->bot)->create([
        'type' => 'command', 'match' => '/start',
        'handler_type' => 'flow', 'flow_id' => $flow->id,
        'status' => 'active',
    ]);

    $this->actingAs($this->admin)
        ->patchJson("/bots/{$this->bot->id}/flows/{$flow->id}/status", ['status' => 'inactive'])
        ->assertOk()
        ->assertJsonPath('affected_routes_count', 1);

    $route->refresh();
    expect($route->status->value)->toBe('draft');
    expect($route->flow_id)->toBeNull();
});

test('PUT on active flow with broken graph auto-drops to draft', function () {
    $flow = BotFlow::factory()->for($this->bot)->create([
        'graph' => [
            'nodes' => [['id' => 'start', 'type' => 'start'], ['id' => 'oc', 'type' => 'on_complete']],
            'edges' => [],
        ],
        'status' => 'active',
    ]);

    $this->actingAs($this->admin)->put("/bots/{$this->bot->id}/flows/{$flow->id}", [
        'graph' => [
            'nodes' => [['id' => 'start', 'type' => 'start']],
            'edges' => [],
        ],
    ])->assertRedirect();

    expect($flow->fresh()->status->value)->toBe('draft');
});
