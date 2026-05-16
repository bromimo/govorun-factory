<?php

namespace Database\Factories;

use App\Models\Bot;
use App\Enums\ConnectionAuthType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** Фабрика подключений бота к внешним API. */
class BotConnectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'bot_id' => Bot::factory(),
            'name' => $this->faker->company(),
            'slug' => $this->faker->unique()->regexify('[a-z][a-z0-9_]{4,12}'),
            'base_url' => $this->faker->url(),
            'auth_type' => ConnectionAuthType::None->value,
            'auth_config' => [],
            'default_headers' => [],
        ];
    }
}
