<?php

namespace Tests\Feature;

use App\Models\Bot;
use App\Models\BotRoute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BotRouteControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Bot $bot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->bot = Bot::factory()->for($this->admin, 'creator')->create();
    }

    public function test_can_create_a_route_for_a_bot(): void
    {
        $response = $this->actingAs($this->admin)->post("/bots/{$this->bot->id}/routes", [
            'type' => 'command',
            'match' => '/start',
            'handler_type' => 'controller',
            'handler_schema' => ['blocks' => [['type' => 'reply_text', 'params' => ['text' => 'Hello']]]],
            'middleware' => [],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bot_routes', [
            'bot_id' => $this->bot->id,
            'type' => 'command',
            'match' => '/start',
        ]);
    }

    public function test_can_create_a_route_with_flow_handler(): void
    {
        $flow = $this->bot->flows()->create(['name' => 'TestFlow', 'graph' => ['nodes' => [], 'edges' => []]]);

        $response = $this->actingAs($this->admin)->post("/bots/{$this->bot->id}/routes", [
            'type' => 'command',
            'match' => '/help',
            'handler_type' => 'flow',
            'flow_id' => $flow->id,
            'middleware' => [],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bot_routes', [
            'handler_type' => 'flow',
            'flow_id' => $flow->id,
        ]);
    }

    public function test_can_update_a_route(): void
    {
        $route = BotRoute::factory()->for($this->bot)->create();

        $response = $this->actingAs($this->admin)->put("/bots/{$this->bot->id}/routes/{$route->id}", [
            'type' => 'phrase',
            'match' => 'hello',
            'handler_type' => 'controller',
            'handler_schema' => ['blocks' => []],
            'middleware' => [],
        ]);

        $response->assertRedirect();
        $this->assertEquals('phrase', $route->fresh()->type->value);
    }

    public function test_can_delete_a_route(): void
    {
        $route = BotRoute::factory()->for($this->bot)->create();

        $response = $this->actingAs($this->admin)->delete("/bots/{$this->bot->id}/routes/{$route->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('bot_routes', ['id' => $route->id]);
    }

    public function test_can_reorder_routes(): void
    {
        $r1 = BotRoute::factory()->for($this->bot)->create(['sort_order' => 0]);
        $r2 = BotRoute::factory()->for($this->bot)->create(['sort_order' => 1]);
        $r3 = BotRoute::factory()->for($this->bot)->create(['sort_order' => 2]);

        $response = $this->actingAs($this->admin)->post("/bots/{$this->bot->id}/routes/reorder", [
            'ids' => [$r3->id, $r1->id, $r2->id],
        ]);

        $response->assertOk();
        $this->assertEquals(0, $r3->fresh()->sort_order);
        $this->assertEquals(1, $r1->fresh()->sort_order);
        $this->assertEquals(2, $r2->fresh()->sort_order);
    }

    public function test_editor_cannot_manage_routes_of_other_users_bot(): void
    {
        $editor = User::factory()->editor()->create();
        $otherBot = Bot::factory()->for($this->admin, 'creator')->create();

        $this->actingAs($editor)->post("/bots/{$otherBot->id}/routes", [
            'type' => 'command',
            'match' => '/hack',
            'handler_type' => 'controller',
            'handler_schema' => ['blocks' => []],
            'middleware' => [],
        ])->assertForbidden();
    }

    public function test_viewer_cannot_create_routes(): void
    {
        $viewer = User::factory()->create();

        $this->actingAs($viewer)->post("/bots/{$this->bot->id}/routes", [
            'type' => 'command',
            'match' => '/test',
            'handler_type' => 'controller',
            'handler_schema' => ['blocks' => []],
            'middleware' => [],
        ])->assertForbidden();
    }

    public function test_validates_required_fields(): void
    {
        $this->actingAs($this->admin)->post("/bots/{$this->bot->id}/routes", [])
            ->assertSessionHasErrors(['type', 'handler_type']);
    }

    public function test_can_create_phrase_route_with_aliases(): void
    {
        $response = $this->actingAs($this->admin)->post("/bots/{$this->bot->id}/routes", [
            'type' => 'phrase',
            'match' => 'запись',
            'aliases' => ['записаться', 'записать'],
            'handler_type' => 'controller',
            'handler_schema' => ['blocks' => []],
            'middleware' => [],
        ]);

        $response->assertRedirect();
        $route = $this->bot->routes()->where('match', 'запись')->first();
        $this->assertEquals(['записаться', 'записать'], $route->aliases);
    }

    public function test_aliases_is_null_for_non_phrase_type(): void
    {
        $this->actingAs($this->admin)->post("/bots/{$this->bot->id}/routes", [
            'type' => 'command',
            'match' => '/start',
            'aliases' => ['old'],
            'handler_type' => 'controller',
            'handler_schema' => ['blocks' => []],
            'middleware' => [],
        ]);

        $route = $this->bot->routes()->where('match', '/start')->first();
        $this->assertNull($route->aliases);
    }

    public function test_empty_aliases_are_filtered_out(): void
    {
        $this->actingAs($this->admin)->post("/bots/{$this->bot->id}/routes", [
            'type' => 'phrase',
            'match' => 'hello',
            'aliases' => ['hi', '', '  ', 'hey'],
            'handler_type' => 'controller',
            'handler_schema' => ['blocks' => []],
            'middleware' => [],
        ]);

        $route = $this->bot->routes()->where('match', 'hello')->first();
        $this->assertEquals(['hi', 'hey'], $route->aliases);
    }

    public function test_cannot_create_second_fallback_route(): void
    {
        BotRoute::factory()->for($this->bot)->create(['type' => 'fallback']);

        $this->actingAs($this->admin)->post("/bots/{$this->bot->id}/routes", [
            'type' => 'fallback',
            'handler_type' => 'controller',
            'handler_schema' => ['blocks' => []],
            'middleware' => [],
        ])->assertSessionHasErrors(['type']);
    }
}
