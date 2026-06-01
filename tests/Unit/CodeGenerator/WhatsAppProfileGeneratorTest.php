<?php

use App\Models\Bot;
use App\Services\CodeGenerator\WhatsAppProfileGenerator;

test('renderConfig returns null when whatsapp disabled', function () {
    $bot = new Bot(['messenger_config' => ['whatsapp' => ['enabled' => false]]]);

    expect((new WhatsAppProfileGenerator)->renderConfig($bot))->toBeNull();
});

test('renderConfig renders profile fields when enabled', function () {
    $bot = new Bot(['messenger_config' => ['whatsapp' => [
        'enabled' => true,
        'profile' => [
            'about' => 'Привет',
            'description' => 'Описание',
            'address' => 'Москва',
            'email' => 'a@b.com',
            'websites' => ['https://example.com'],
            'vertical' => 'RETAIL',
        ],
    ]]]);

    $php = (new WhatsAppProfileGenerator)->renderConfig($bot);

    expect($php)
        ->toStartWith('<?php')
        ->toContain("'about' => 'Привет'")
        ->toContain("'vertical' => 'RETAIL'")
        ->toContain("'https://example.com'");
});
