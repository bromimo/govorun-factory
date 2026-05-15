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
            'type'          => 'photo',
            'original_name' => 'image.jpg',
            'filename'      => uniqid('media_') . '.jpg',
            'mime_type'     => 'image/jpeg',
            'size'          => 102400,
            'width'         => 1280,
            'height'        => 720,
        ];
    }
}