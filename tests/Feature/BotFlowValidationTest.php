<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Bot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/** Тесты сохранения правил валидации в graph flow. */
class BotFlowValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_flow_graph_stores_validation_rules(): void
    {
        $user = User::factory()->admin()->create();
        $bot = Bot::factory()->for($user, 'creator')->create();
        $flow = $bot->flows()->create([
            'name' => 'TestFlow',
            'graph' => ['nodes' => [], 'edges' => []],
        ]);

        $graph = [
            'nodes' => [
                [
                    'id' => 'ask_text_1',
                    'type' => 'ask_text',
                    'data' => [
                        'text' => 'Введите email',
                        'validation' => [
                            ['name' => 'required', 'message' => 'Обязательное поле'],
                            ['name' => 'email'],
                            ['name' => 'max', 'params' => [255]],
                        ],
                    ],
                    'position' => ['x' => 0, 'y' => 0],
                ],
            ],
            'edges' => [],
        ];

        $this->actingAs($user)
            ->put(route('bot-flows.update', [$bot, $flow]), [
                'name' => $flow->name,
                'graph' => $graph,
            ])
            ->assertRedirect();

        $flow->refresh();
        $node = $flow->graph['nodes'][0];

        $this->assertEquals('ask_text_1', $node['id']);
        $this->assertCount(3, $node['data']['validation']);
        $this->assertEquals('required', $node['data']['validation'][0]['name']);
        $this->assertEquals('Обязательное поле', $node['data']['validation'][0]['message']);
        $this->assertEquals([255], $node['data']['validation'][2]['params']);
    }

    public function test_bot_config_stores_validation_messages(): void
    {
        $user = User::factory()->admin()->create();
        $bot = Bot::factory()->for($user, 'creator')->create();

        $this->actingAs($user)
            ->put(route('bots.update', $bot), [
                'config' => [
                    'validation_messages' => [
                        'required' => 'Заполните поле',
                        'email' => 'Некорректный email',
                    ],
                ],
            ])
            ->assertRedirect();

        $bot->refresh();
        $this->assertEquals('Заполните поле', $bot->config['validation_messages']['required']);
        $this->assertEquals('Некорректный email', $bot->config['validation_messages']['email']);
    }
}
