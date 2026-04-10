<?php

namespace App\Services\CodeGenerator;

/** Генератор конфигурационных файлов проекта. */
class ConfigGenerator
{
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

     * @param  array<string, array<string, string>>  $drivers
     */
    public function generateMessengerConfig(array $drivers): string
    {
        return "<?php\n\n".view('stubs.config_messenger', compact('drivers'))->render();
    }

    /** Сгенерировать config/database.php.
     */
    public function generateDatabaseConfig(): string
    {
        return "<?php\n\n".view('stubs.config_database')->render();
    }

    /** Сгенерировать .env файл.
     * @param  array<string, array<string, string>>  $drivers
     */
    public function generateEnv(string $botName, string $environment, bool $debug, array $drivers): string
    {
        return view('stubs.env', compact('botName', 'environment', 'debug', 'drivers'))->render();
    }
}
