<?php

namespace App\Services\CodeGenerator;

use App\Models\Bot;

/** Генератор Telegram-профиля бота: config/bot_profile.php + бинарь фото. */
class BotProfileGenerator
{
    /** Сгенерировать содержимое config/bot_profile.php или null если Telegram отключён.
     * @param Bot $bot
     * @return string|null
     */
    public function renderConfig(Bot $bot): ?string
    {
        if (($bot->messenger_config['telegram']['enabled'] ?? false) !== true) {
            return null;
        }

        $profile = $bot->messenger_config['telegram']['profile'] ?? [];

        return "<?php\n\n" . view('stubs.config_bot_profile', [
            'name' => $profile['name'] ?? '',
            'shortDescription' => $profile['short_description'] ?? '',
            'description' => $profile['description'] ?? '',
            'commands' => $profile['commands'] ?? [],
        ])->render();
    }

    /** Получить пути исходного и целевого файлов фото или null если фото нет.
     * @param Bot $bot
     * @return array{source_absolute_path: string, zip_relative_path: string}|null
     */
    public function resolvePhoto(Bot $bot): ?array
    {
        $relativePath = $bot->messenger_config['telegram']['profile']['photo_path'] ?? null;
        if ($relativePath === null) {
            return null;
        }

        $absolute = storage_path('app/' . $relativePath);
        if (! is_file($absolute)) {
            return null;
        }

        $extension = pathinfo($relativePath, PATHINFO_EXTENSION);

        return [
            'source_absolute_path' => $absolute,
            'zip_relative_path' => "storage/app/bot-profile.{$extension}",
        ];
    }
}
