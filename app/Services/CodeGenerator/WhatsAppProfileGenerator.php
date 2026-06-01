<?php

namespace App\Services\CodeGenerator;

use App\Models\Bot;
use Illuminate\Support\Facades\Storage;

/** Генератор WhatsApp-профиля бота: config/whatsapp_profile.php + бинарь фото. */
class WhatsAppProfileGenerator
{
    /** Сгенерировать содержимое config/whatsapp_profile.php или null если WhatsApp отключён.
     * @param  Bot  $bot  Бот
     * @return ?string PHP-код конфига или null
     */
    public function renderConfig(Bot $bot): ?string
    {
        if (($bot->messenger_config['whatsapp']['enabled'] ?? false) !== true) {
            return null;
        }

        $profile = $bot->messenger_config['whatsapp']['profile'] ?? [];

        return "<?php\n\n".view('stubs.config_whatsapp_profile', [
            'about' => $profile['about'] ?? '',
            'description' => $profile['description'] ?? '',
            'address' => $profile['address'] ?? '',
            'email' => $profile['email'] ?? '',
            'websites' => $profile['websites'] ?? [],
            'vertical' => $profile['vertical'] ?? 'UNDEFINED',
        ])->render();
    }

    /** Получить пути исходного и целевого файлов фото или null если фото нет.
     * @param  Bot  $bot  Бот
     * @return array{source_absolute_path: string, zip_relative_path: string}|null
     */
    public function resolvePhoto(Bot $bot): ?array
    {
        $relativePath = $bot->messenger_config['whatsapp']['profile']['photo_path'] ?? null;
        if ($relativePath === null) {
            return null;
        }

        $absolute = Storage::disk('local')->path($relativePath);
        if (! is_file($absolute)) {
            return null;
        }

        $extension = pathinfo($relativePath, PATHINFO_EXTENSION) ?: 'jpg';

        return [
            'source_absolute_path' => $absolute,
            'zip_relative_path' => "storage/app/whatsapp-profile.{$extension}",
        ];
    }
}
