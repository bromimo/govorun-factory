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

    /** Сгенерировать config/app.php.
     * @param  array<string, mixed>  $config
     */
    public function generateAppConfig(string $botName, array $config): string
    {
        return "<?php\n\n".view('stubs.config_app', [
            'botName' => $botName,
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
        return "<?php\n\n".view('stubs.config_messenger', compact('drivers'))->render();
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
        return view('stubs.env_example', compact('botName', 'drivers'))->render();
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
