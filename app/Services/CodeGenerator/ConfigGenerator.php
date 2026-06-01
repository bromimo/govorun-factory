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
        'viber' => [
            'auth_token' => 'VIBER_AUTH_TOKEN',
        ],
        'whatsapp' => [
            'access_token' => 'WHATSAPP_ACCESS_TOKEN',
            'phone_number_id' => 'WHATSAPP_PHONE_NUMBER_ID',
            'waba_id' => 'WHATSAPP_WABA_ID',
            'app_id' => 'WHATSAPP_APP_ID',
            'verify_token' => 'WHATSAPP_VERIFY_TOKEN',
            'app_secret' => 'WHATSAPP_APP_SECRET',
            'api_version' => 'WHATSAPP_API_VERSION',
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
        'viber' => [
            'title' => 'Viber',
            'description' => [
                'Параметры подключения к Viber Bot API.',
                'auth_token выдаётся в кабинете Viber Public Account и используется',
                'как для авторизации исходящих запросов, так и для проверки подписи',
                'входящих webhook-событий.',
            ],
        ],
        'whatsapp' => [
            'title' => 'WhatsApp',
            'description' => [
                'Параметры подключения к WhatsApp Cloud API (Meta Graph API).',
                'access_token, phone_number_id, waba_id, app_id выдаются в Meta App',
                'Dashboard → WhatsApp. verify_token и app_secret нужны для проверки',
                'входящих webhook-запросов. api_version — версия Graph API (напр. v25.0).',
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
            : array_keys(array_filter($messengerConfig, fn ($entry) => ($entry['enabled'] ?? false) === true));

        $drivers = [];
        foreach ($driverNames as $driverName) {
            if (isset(self::DRIVER_FIELDS[$driverName])) {
                $drivers[$driverName] = self::DRIVER_FIELDS[$driverName];
            }
        }

        return $drivers;
    }
}
