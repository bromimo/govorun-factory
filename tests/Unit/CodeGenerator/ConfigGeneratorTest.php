<?php

use App\Services\CodeGenerator\ConfigGenerator;

test('generates app config from bot settings', function () {
    $config = [
        'environment' => 'production',
        'debug' => false,
        'state_storage' => 'database',
    ];

    $generator = new ConfigGenerator;
    $result = $generator->generateAppConfig('My Bot', 'my_bot', $config);

    expect($result)->toContain("'name' => 'My Bot'");
    expect($result)->toContain("'environment' => 'production'");
    expect($result)->toContain("'debug' => false");
    expect($result)->toContain("'state_storage' => 'database'");
});

test('app config includes laravel-style banners for each key', function () {
    $config = [
        'environment' => 'production',
        'debug' => false,
        'state_storage' => 'database',
    ];

    $result = (new ConfigGenerator)->generateAppConfig('My Bot', 'my_bot', $config);

    expect($result)
        ->toContain('| Имя приложения')
        ->toContain('| URL приложения')
        ->toContain('| Окружение')
        ->toContain('| Режим отладки')
        ->toContain('| Хранилище состояния');
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

test('messenger config includes banners for default, drivers and each driver block', function () {
    $drivers = [
        'telegram' => ['token' => 'TELEGRAM_BOT_TOKEN', 'secret' => 'TELEGRAM_WEBHOOK_SECRET'],
        'vk' => ['token' => 'VK_BOT_TOKEN', 'secret' => 'VK_SECRET', 'confirmation' => 'VK_CONFIRMATION'],
    ];

    $result = (new ConfigGenerator)->generateMessengerConfig($drivers);

    expect($result)
        ->toContain('| Драйвер мессенджера по умолчанию')
        ->toContain('| Активные драйверы')
        ->toContain('| Telegram')
        ->toContain('| ВКонтакте');
});

test('database config includes banners for driver and connection', function () {
    $result = (new ConfigGenerator)->generateDatabaseConfig();

    expect($result)
        ->toContain('| Драйвер базы данных')
        ->toContain('| Подключение');
});

test('generates .env.example with correct env var names', function () {
    $drivers = ['telegram' => ['token' => 'TELEGRAM_BOT_TOKEN', 'secret' => 'TELEGRAM_WEBHOOK_SECRET']];

    $generator = new ConfigGenerator;
    $result = $generator->generateEnvExample('Test Bot', $drivers);

    expect($result)->toContain('APP_NAME="Test Bot"');
    expect($result)->toContain('TELEGRAM_BOT_TOKEN=');
    expect($result)->toContain('TELEGRAM_WEBHOOK_SECRET=');
});

test('env example includes section headers', function () {
    $drivers = [
        'telegram' => ['token' => 'TELEGRAM_BOT_TOKEN', 'secret' => 'TELEGRAM_WEBHOOK_SECRET'],
    ];

    $result = (new ConfigGenerator)->generateEnvExample('Test Bot', $drivers);

    expect($result)
        ->toContain('# Приложение')
        ->toContain('# Мессенджер')
        ->toContain('# Telegram')
        ->toContain('# База данных');
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

test('exposes driver descriptions for known drivers', function () {
    expect(ConfigGenerator::DRIVER_DESCRIPTIONS)
        ->toHaveKey('telegram')
        ->toHaveKey('vk')
        ->toHaveKey('viber');

    expect(ConfigGenerator::DRIVER_DESCRIPTIONS['telegram'])
        ->toHaveKey('title')
        ->toHaveKey('description');
});

test('messenger config supports viber driver', function () {
    $drivers = [
        'viber' => ['auth_token' => 'VIBER_AUTH_TOKEN'],
    ];

    $result = (new ConfigGenerator)->generateMessengerConfig($drivers);

    expect($result)
        ->toContain("'viber'")
        ->toContain("env('VIBER_AUTH_TOKEN', '')")
        ->toContain('| Viber');
});

test('env example includes viber auth token when viber enabled', function () {
    $drivers = ['viber' => ['auth_token' => 'VIBER_AUTH_TOKEN']];

    $result = (new ConfigGenerator)->generateEnvExample('Test Bot', $drivers);

    expect($result)->toContain('VIBER_AUTH_TOKEN=');
});

test('resolves viber driver fields from messenger config', function () {
    $messengerConfig = [
        'viber' => ['enabled' => true, 'profile' => ['sender_name' => 'Bot']],
    ];

    $result = (new ConfigGenerator)->resolveDriverFields($messengerConfig);

    expect($result)->toHaveKey('viber')
        ->and($result['viber'])->toBe(['auth_token' => 'VIBER_AUTH_TOKEN']);
});

test('resolves driver fields skips disabled drivers in object format', function () {
    $messengerConfig = [
        'telegram' => ['enabled' => true],
        'viber' => ['enabled' => true],
        'vk' => ['enabled' => false],
    ];

    $result = (new ConfigGenerator)->resolveDriverFields($messengerConfig);

    expect($result)->toHaveKey('telegram')
        ->toHaveKey('viber')
        ->not->toHaveKey('vk');
});

test('messenger config drivers array contains all active drivers hardcoded', function () {
    $drivers = [
        'telegram' => ['token' => 'TELEGRAM_BOT_TOKEN', 'secret' => 'TELEGRAM_WEBHOOK_SECRET'],
        'viber' => ['auth_token' => 'VIBER_AUTH_TOKEN'],
    ];

    $result = (new ConfigGenerator)->generateMessengerConfig($drivers);

    expect($result)
        ->toContain("'telegram',")
        ->toContain("'viber',")
        ->not->toContain('MESSENGER_DRIVER');
});

test('env example does not contain MESSENGER_DRIVER', function () {
    $drivers = ['telegram' => ['token' => 'TELEGRAM_BOT_TOKEN', 'secret' => 'TELEGRAM_WEBHOOK_SECRET']];

    $result = (new ConfigGenerator)->generateEnvExample('Test Bot', $drivers);

    expect($result)->not->toContain('MESSENGER_DRIVER');
});
