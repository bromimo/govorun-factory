<?php

use App\Models\Bot;
use App\Models\User;
use App\Services\CodeGenerator\ViberProfileGenerator;
use Illuminate\Support\Facades\Storage;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('returns null when viber is disabled', function () {
    $bot = Bot::factory()->for(User::factory(), 'creator')->create([
        'messenger_config' => ['viber' => ['enabled' => false]],
    ]);

    $result = (new ViberProfileGenerator)->renderConfig($bot);

    expect($result)->toBeNull();
});

test('renders config when viber enabled', function () {
    $bot = Bot::factory()->for(User::factory(), 'creator')->create([
        'messenger_config' => [
            'viber' => [
                'enabled' => true,
                'profile' => [
                    'sender_name' => 'MyBot',
                    'public_account_uri' => 'mybot',
                    'event_types' => ['message', 'conversation_started'],
                    'avatar_path' => null,
                ],
            ],
        ],
    ]);

    $result = (new ViberProfileGenerator)->renderConfig($bot);

    expect($result)
        ->toContain("'sender_name' => 'MyBot'")
        ->toContain("'public_account_uri' => 'mybot'")
        ->toContain("'message'")
        ->toContain("'conversation_started'")
        ->toContain("'sender_avatar' => null");
});

test('renders sender_avatar as APP_URL + storage path when avatar present', function () {
    $bot = Bot::factory()->for(User::factory(), 'creator')->create([
        'messenger_config' => [
            'viber' => [
                'enabled' => true,
                'profile' => [
                    'sender_name' => 'B',
                    'public_account_uri' => null,
                    'event_types' => ['message'],
                    'avatar_path' => 'bots/1/viber/avatar.jpg',
                ],
            ],
        ],
    ]);

    $result = (new ViberProfileGenerator)->renderConfig($bot);

    expect($result)->toContain("env('APP_URL') . '/storage/viber-avatar.jpg'");
});

test('resolveAvatar returns null when path missing', function () {
    $bot = Bot::factory()->for(User::factory(), 'creator')->create([
        'messenger_config' => ['viber' => ['enabled' => true, 'profile' => ['avatar_path' => null]]],
    ]);

    expect((new ViberProfileGenerator)->resolveAvatar($bot))->toBeNull();
});

test('resolveAvatar returns source and zip paths when file exists', function () {
    Storage::fake('local');
    Storage::disk('local')->put('bots/1/viber/avatar.jpg', 'fake');

    $bot = Bot::factory()->for(User::factory(), 'creator')->create([
        'messenger_config' => [
            'viber' => ['enabled' => true, 'profile' => ['avatar_path' => 'bots/1/viber/avatar.jpg']],
        ],
    ]);

    $result = (new ViberProfileGenerator)->resolveAvatar($bot);

    expect($result)->toHaveKey('source_absolute_path')
        ->and($result)->toHaveKey('zip_relative_path')
        ->and($result['zip_relative_path'])->toBe('storage/app/public/viber-avatar.jpg');
});