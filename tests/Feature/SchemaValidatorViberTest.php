<?php

namespace Tests\Feature;

use App\Models\Bot;
use Tests\TestCase;
use App\Models\User;
use App\Services\SchemaValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SchemaValidatorViberTest extends TestCase
{
    use RefreshDatabase;

    public function test_bot_with_only_viber_passes_messenger_check(): void
    {
        $bot = Bot::factory()->for(User::factory(), 'creator')->create([
            'messenger_config' => [
                'viber' => ['enabled' => true, 'profile' => ['sender_name' => 'B', 'event_types' => ['message']]],
            ],
        ]);
        $bot->routes()->create([
            'type' => 'command', 'match' => '/start',
            'handler_type' => 'controller', 'handler_schema' => ['blocks' => []],
            'sort_order' => 0, 'status' => 'active',
        ]);

        $result = (new SchemaValidator($bot))->validate();

        $errors = $result->errors;
        $messengerErrors = array_filter($errors, fn ($e) => str_contains($e, 'мессенджер'));
        $this->assertEmpty($messengerErrors, 'Бот с включённым Viber не должен ругаться на отсутствие мессенджера');
    }
}
