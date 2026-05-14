<?php

namespace App\Services\CodeGenerator;

/** Генератор конфигурационных файлов проекта. */
class ConfigGenerator
{
    /** Конфигурация драйверов: config-ключ → env-переменная. */
    public const DRIVER_FIELDS = [
        'telegram' => [
            'token' => 'TELEGRAM_BOT_TOKEN',
            'secret' => 'TELEGRAM_WEBHOOK_SECRET',
        ],
        'vk' => [
            'token' => 'VK_BOT_TOKEN',
            'secret' => 'VK_SECRET',
            'confirmation' => 'VK_CONFIRMATION',
        ],
    ];

    /** Описания драйверов для баннеров в config/messenger.php и .env.example.
     *  description — массив строк (по одной на строку баннера), чтобы избегать авто-переноса
     *  и контролировать ширину для UTF-8 текстов.
     */
    public const DRIVER_DESCRIPTIONS = [
        'telegram' => [
            'title' => 'Telegram',
            'description' => [
                'Параметры подключения к Telegram Bot API.',
                'Токен выдаёт @BotFather, secret — секретный ключ для верификации',
                'входящих webhook-запросов.',
            ],
        ],
        'vk' => [
            'title' => 'ВКонтакте',
            'description' => [
                'Параметры подключения к VK Callback API.',
                'Токен сообщества, secret и confirmation выдаются в настройках',
                'сообщества VK.',
            ],
        ],
    ];

    /** Сгенерировать config/app.php.
     * @param  array<string, mixed>  $config
     */
    public function generateAppConfig(string $botName, string $botUsername, array $config): string
    {
        return "<?php\n\n".view('stubs.config_app', [
            'botName' => $botName,
            'botUsername' => $botUsername,
            'environment' => $config['environment'] ?? 'production',
            'debug' => $config['debug'] ?? false,
            'stateStorage' => $config['state_storage'] ?? 'file',
        ])->render();
    }

    /** Сгенерировать config/messenger.php.
     * @param  array<string, array<int, string>>  $drivers
     */
    public function generateMessengerConfig(array $drivers): string
    {
        $descriptions = self::DRIVER_DESCRIPTIONS;

        return "<?php\n\n".view('stubs.config_messenger', compact('drivers', 'descriptions'))->render();
    }

    /** Сгенерировать config/database.php. */
    public function generateDatabaseConfig(): string
    {
        return "<?php\n\n".view('stubs.config_database')->render();
    }

    /** Сгенерировать .env.example с пустыми значениями секретов.
     * @param  array<string, array<int, string>>  $drivers
     */
    public function generateEnvExample(string $botName, array $drivers): string
    {
        $descriptions = self::DRIVER_DESCRIPTIONS;

        return view('stubs.env_example', compact('botName', 'drivers', 'descriptions'))->render();
    }

    /** Получить поля окружения для включённых драйверов.
     * @param  array<int|string, mixed>  $messengerConfig
     * @return array<string, array<int, string>>
     */
    public function resolveDriverFields(array $messengerConfig): array
    {
        $driverNames = array_is_list($messengerConfig)
            ? $messengerConfig
            : array_keys($messengerConfig);

        $drivers = [];
        foreach ($driverNames as $driverName) {
            if (isset(self::DRIVER_FIELDS[$driverName])) {
                $drivers[$driverName] = self::DRIVER_FIELDS[$driverName];
            }
        }

        return $drivers;
    }
}
