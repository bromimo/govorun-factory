<?php

namespace Database\Factories;

use App\Models\Bot;
use Illuminate\Database\Eloquent\Factories\Factory;

/** Фабрика для модели BotFlow. */
class BotFlowFactory extends Factory
{
    /** Определение состояния модели по умолчанию.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bot_id' => Bot::factory(),
            'name' => fake()->words(2, true).' Flow',
            'description' => fake()->sentence(),
            'graph' => ['nodes' => [], 'edges' => []],
            'interrupt_commands' => [],
            'interrupt_on_event' => false,
        ];
    }
}