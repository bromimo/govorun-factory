<?php

namespace Database\Factories;

use App\Enums\HandlerType;
use App\Enums\RouteType;
use App\Models\Bot;
use Illuminate\Database\Eloquent\Factories\Factory;

class BotRouteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'bot_id' => Bot::factory(),
            'type' => RouteType::Command->value,
            'match' => '/' . fake()->word(),
            'handler_type' => HandlerType::Controller->value,
            'handler_schema' => ['blocks' => [['type' => 'reply_text', 'params' => ['text' => fake()->sentence()]]]],
            'middleware' => [],
            'sort_order' => 0,
        ];
    }
}
