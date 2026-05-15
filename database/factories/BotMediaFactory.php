<?php

namespace Database\Factories;

use App\Models\Bot;
use Illuminate\Database\Eloquent\Factories\Factory;

/** Фабрика для модели BotMedia.
 *
 * @extends Factory<\App\Models\BotMedia>
 */
class BotMediaFactory extends Factory
{
    /** Определение состояния модели по умолчанию.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bot_id'        => Bot::factory(),
            'type'          => fake()->randomElement(['photo', 'video', 'audio', 'document', 'animation']),
            'original_name' => fake()->word() . '.jpg',
            'filename'      => fake()->unique()->uuid() . '.jpg',
            'mime_type'     => 'image/jpeg',
            'size'          => fake()->numberBetween(1024, 10485760),
            'width'         => fake()->numberBetween(100, 5000),
            'height'        => fake()->numberBetween(100, 5000),
        ];
    }
}