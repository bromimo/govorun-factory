<?php

use App\Services\CodeGenerator\ConfigGenerator;

test('generates app config from bot settings', function () {
    $config = [
        'environment' => 'production',
        'debug' => false,
        'state_storage' => 'database',
    ];

    $generator = new ConfigGenerator;
    $result = $generator->generateAppConfig('My Bot', $config);

    expect($result)->toContain("'name' => 'My Bot'");
    expect($result)->toContain("'environment' => 'production'");
    expect($result)->toContain("'debug' => false");
    expect($result)->toContain("'state_storage' => 'database'");
});

test('generates messenger config with correct env vars', function () {
    $drivers = [
        'telegram' => ['token' => 'TELEGRAM_BOT_TOKEN', 'secret' => 'TELEGRAM_WEBHOOK_SECRET'],
    ];

    $generator = new ConfigGenerator;
    $result = $generator->generateMessengerConfig($drivers);

    expect($result)->toContain("'telegram'");
    expect($result)->toContain("env('TELEGRAM_BOT_TOKEN', '')");
    expect($result)->toContain("env('TELEGRAM_WEBHOOK_SECRET', null)");
    expect($result)->toContain("'drivers'");
});

test('generates .env.example with correct env var names', function () {
    $drivers = ['telegram' => ['token' => 'TELEGRAM_BOT_TOKEN', 'secret' => 'TELEGRAM_WEBHOOK_SECRET']];

    $generator = new ConfigGenerator;
    $result = $generator->generateEnvExample('Test Bot', $drivers);

    expect($result)->toContain('APP_NAME="Test Bot"');
    expect($result)->toContain('TELEGRAM_BOT_TOKEN=');
    expect($result)->toContain('TELEGRAM_WEBHOOK_SECRET=');
});

test('resolves driver fields from list format', function () {
    $generator = new ConfigGenerator;

    $result = $generator->resolveDriverFields(['telegram', 'vk']);

    expect($result)->toBe([
        'telegram' => ['token' => 'TELEGRAM_BOT_TOKEN', 'secret' => 'TELEGRAM_WEBHOOK_SECRET'],
        'vk' => ['token' => 'VK_BOT_TOKEN', 'secret' => 'VK_SECRET', 'confirmation' => 'VK_CONFIRMATION'],
    ]);
});

test('resolves driver fields ignores unknown drivers', function () {
    $generator = new ConfigGenerator;

    $result = $generator->resolveDriverFields(['unknown']);

    expect($result)->toBe([]);
});
