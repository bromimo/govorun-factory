<?php

namespace Tests\Feature;

use App\Models\Bot;
use App\Models\BotFlow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BotFlowControllerTest extends TestCase
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

    public function test_can_create_a_flow(): void
    {
        $response = $this->actingAs($this->admin)->post("/bots/{$this->bot->id}/flows", [
            'name' => 'OnboardingFlow',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bot_flows', [
            'bot_id' => $this->bot->id,
            'name' => 'OnboardingFlow',
        ]);
    }

    public function test_flow_editor_page_loads(): void
    {
        $flow = $this->bot->flows()->create([
            'name' => 'TestFlow',
            'graph' => ['nodes' => [], 'edges' => []],
        ]);

        $response = $this->actingAs($this->admin)->get("/bots/{$this->bot->id}/flows/{$flow->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Flows/Edit')
            ->has('bot')
            ->has('flow')
            ->where('flow.id', $flow->id)
        );
    }

    public function test_can_update_flow_graph(): void
    {
        $flow = $this->bot->flows()->create([
            'name' => 'TestFlow',
            'graph' => ['nodes' => [], 'edges' => []],
        ]);

        $newGraph = [
            'nodes' => [['id' => 'step1', 'type' => 'ask_text', 'data' => ['text' => 'Hi'], 'position' => ['x' => 100, 'y' => 100]]],
            'edges' => [],
        ];

        $response = $this->actingAs($this->admin)->put("/bots/{$this->bot->id}/flows/{$flow->id}", [
            'name' => 'UpdatedFlow',
            'graph' => $newGraph,
            'interrupt_commands' => ['/cancel'],
            'interrupt_on_event' => true,
        ]);

        $response->assertRedirect();
        $flow->refresh();
        $this->assertEquals('UpdatedFlow', $flow->name);
        $this->assertCount(1, $flow->graph['nodes']);
        $this->assertEquals(['/cancel'], $flow->interrupt_commands);
        $this->assertTrue($flow->interrupt_on_event);
    }

    public function test_can_delete_a_flow(): void
    {
        $flow = $this->bot->flows()->create(['name' => 'ToDelete', 'graph' => ['nodes' => [], 'edges' => []]]);

        $response = $this->actingAs($this->admin)->delete("/bots/{$this->bot->id}/flows/{$flow->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('bot_flows', ['id' => $flow->id]);
    }

    public function test_editor_cannot_manage_flows_of_other_users_bot(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)->post("/bots/{$this->bot->id}/flows", [
            'name' => 'Hacked',
        ])->assertForbidden();
    }

    public function test_create_flow_validates_name(): void
    {
        $this->actingAs($this->admin)->post("/bots/{$this->bot->id}/flows", [])
            ->assertSessionHasErrors(['name']);
    }
}
