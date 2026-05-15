<?php

namespace Tests\Feature;

use App\Models\Bot;
use Tests\TestCase;
use App\Models\User;
use App\Models\BotRoute;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BotUpdatedByTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_bot_sets_updated_by_to_creator(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/bots', ['name' => 'Fresh']);

        $bot = Bot::where('name', 'Fresh')->first();
        $this->assertNotNull($bot);
        $this->assertEquals($admin->id, $bot->updated_by);
    }

    public function test_updating_bot_settings_sets_updated_by(): void
    {
        $creator = User::factory()->editor()->create();
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($creator, 'creator')->create(['updated_by' => $creator->id]);

        $this->actingAs($admin)->put("/bots/{$bot->id}", ['name' => 'Renamed']);

        $this->assertEquals($admin->id, $bot->fresh()->updated_by);
    }

    public function test_creating_route_touches_bot_and_sets_updated_by(): void
    {
        $creator = User::factory()->editor()->create();
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($creator, 'creator')->create([
            'updated_by' => $creator->id,
            'updated_at' => now()->subDay(),
        ]);
        $originalUpdatedAt = $bot->updated_at;

        $this->actingAs($admin)->post("/bots/{$bot->id}/routes", [
            'type' => 'command',
            'match' => '/start',
            'handler_type' => 'controller',
            'handler_schema' => ['blocks' => []],
            'middleware' => [],
        ])->assertRedirect();

        $bot->refresh();
        $this->assertEquals($admin->id, $bot->updated_by);
        $this->assertTrue($bot->updated_at->greaterThan($originalUpdatedAt));
    }

    public function test_updating_route_touches_bot_and_sets_updated_by(): void
    {
        $creator = User::factory()->editor()->create();
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($creator, 'creator')->create(['updated_by' => $creator->id]);
        $route = BotRoute::factory()->for($bot)->create();

        $this->actingAs($admin)->put("/bots/{$bot->id}/routes/{$route->id}", [
            'type' => 'phrase',
            'match' => 'hello',
            'handler_type' => 'controller',
            'handler_schema' => ['blocks' => []],
            'middleware' => [],
        ])->assertRedirect();

        $this->assertEquals($admin->id, $bot->fresh()->updated_by);
    }

    public function test_deleting_route_touches_bot_and_sets_updated_by(): void
    {
        $creator = User::factory()->editor()->create();
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($creator, 'creator')->create(['updated_by' => $creator->id]);
        $route = BotRoute::factory()->for($bot)->create();

        $this->actingAs($admin)->delete("/bots/{$bot->id}/routes/{$route->id}")
            ->assertRedirect();

        $this->assertEquals($admin->id, $bot->fresh()->updated_by);
    }

    public function test_creating_flow_touches_bot_and_sets_updated_by(): void
    {
        $creator = User::factory()->editor()->create();
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($creator, 'creator')->create(['updated_by' => $creator->id]);

        $this->actingAs($admin)->post("/bots/{$bot->id}/flows", [
            'name' => 'Onboarding',
        ])->assertRedirect();

        $this->assertEquals($admin->id, $bot->fresh()->updated_by);
    }

    public function test_updating_flow_touches_bot_and_sets_updated_by(): void
    {
        $creator = User::factory()->editor()->create();
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($creator, 'creator')->create(['updated_by' => $creator->id]);
        $flow = $bot->flows()->create([
            'name' => 'F',
            'graph' => [
                'nodes' => [['id' => 'start', 'type' => 'start', 'position' => ['x' => 0, 'y' => 0], 'data' => []]],
                'edges' => [],
            ],
        ]);

        $this->actingAs($admin)->put("/bots/{$bot->id}/flows/{$flow->id}", [
            'name' => 'F2',
            'graph' => [
                'nodes' => [['id' => 'start', 'type' => 'start', 'position' => ['x' => 0, 'y' => 0], 'data' => []]],
                'edges' => [],
            ],
        ])->assertRedirect();

        $this->assertEquals($admin->id, $bot->fresh()->updated_by);
    }

    public function test_deleting_flow_touches_bot_and_sets_updated_by(): void
    {
        $creator = User::factory()->editor()->create();
        $admin = User::factory()->admin()->create();
        $bot = Bot::factory()->for($creator, 'creator')->create(['updated_by' => $creator->id]);
        $flow = $bot->flows()->create([
            'name' => 'F',
            'graph' => [
                'nodes' => [['id' => 'start', 'type' => 'start', 'position' => ['x' => 0, 'y' => 0], 'data' => []]],
                'edges' => [],
            ],
        ]);

        $this->actingAs($admin)->delete("/bots/{$bot->id}/flows/{$flow->id}")
            ->assertRedirect();

        $this->assertEquals($admin->id, $bot->fresh()->updated_by);
    }

    public function test_deleting_updater_user_nulls_bot_updated_by(): void
    {
        $creator = User::factory()->admin()->create();
        $updater = User::factory()->editor()->create();
        $bot = Bot::factory()->for($creator, 'creator')->create(['updated_by' => $updater->id]);

        $updater->delete();

        $this->assertNull($bot->fresh()->updated_by);
        $this->assertDatabaseHas('bots', ['id' => $bot->id]);
    }

    public function test_dashboard_loads_updater_relation(): void
    {
        $admin = User::factory()->admin()->create();
        Bot::factory()->for($admin, 'creator')->create(['updated_by' => $admin->id]);

        $response = $this->actingAs($admin)->get('/');

        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard/Index')
            ->has('bots.0.updater')
            ->where('bots.0.updater.id', $admin->id)
            ->where('bots.0.updater.name', $admin->name)
        );
    }
}
