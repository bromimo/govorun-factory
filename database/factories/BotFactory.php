<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** Фабрика для модели Bot. */
class BotFactory extends Factory
{
    /** Определение состояния модели по умолчанию.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true).' Bot',
            'description' => fake()->sentence(),
            'config' => [
                'environment' => 'development',
                'debug' => true,
                'state_storage' => 'file',
            ],
            'messenger_config' => [],
            'created_by' => User::factory(),
        ];
    }
}
