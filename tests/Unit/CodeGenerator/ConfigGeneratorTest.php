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

test('generates messenger config', function () {
    $drivers = [
        'telegram' => ['token' => 'abc123'],
        'vk' => ['token' => 'xyz', 'secret' => 'sec'],
    ];

    $generator = new ConfigGenerator;
    $result = $generator->generateMessengerConfig($drivers);

    expect($result)->toContain("'telegram'");
    expect($result)->toContain("env('TELEGRAM_TOKEN', '')");
    expect($result)->toContain("'vk'");
    expect($result)->toContain("env('VK_SECRET', '')");
});

test('generates .env file', function () {
    $drivers = ['telegram' => ['token' => 'my-token']];

    $generator = new ConfigGenerator;
    $result = $generator->generateEnv('Test Bot', 'production', false, $drivers);

    expect($result)->toContain('APP_NAME="Test Bot"');
    expect($result)->toContain('TELEGRAM_TOKEN=my-token');
});
