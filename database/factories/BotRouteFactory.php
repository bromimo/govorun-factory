<?php

namespace Database\Factories;

use App\Enums\HandlerType;
use App\Enums\RouteType;
use App\Models\Bot;
use Illuminate\Database\Eloquent\Factories\Factory;

/** Фабрика для модели BotRoute. */
class BotRouteFactory extends Factory
{
    /** Определение состояния модели по умолчанию.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bot_id' => Bot::factory(),
            'type' => RouteType::Command->value,
            'match' => '/'.fake()->word(),
            'handler_type' => HandlerType::Controller->value,
            'handler_schema' => ['blocks' => [['type' => 'reply_text', 'params' => ['text' => fake()->sentence()]]]],
            'middleware' => [],
            'sort_order' => 0,
        ];
    }
}
