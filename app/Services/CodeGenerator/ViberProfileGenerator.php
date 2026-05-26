<?php

namespace App\Services\CodeGenerator;

use App\Models\Bot;
use Illuminate\Support\Facades\Storage;

/** Генератор Viber-профиля бота: config/viber_profile.php + бинарь аватара. */
class ViberProfileGenerator
{
    /** Сгенерировать содержимое config/viber_profile.php или null если Viber отключён.
     * @param Bot $bot
     * @return string|null
     */
    public function renderConfig(Bot $bot): ?string
    {
        if (($bot->messenger_config['viber']['enabled'] ?? false) !== true) {
            return null;
        }

        $profile = $bot->messenger_config['viber']['profile'] ?? [];
        $avatarPath = $profile['avatar_path'] ?? null;

        $avatarExpr = 'null';
        if ($avatarPath !== null) {
            $extension = pathinfo($avatarPath, PATHINFO_EXTENSION) ?: 'jpg';
            $avatarExpr = "env('APP_URL') . '/storage/viber-avatar.{$extension}'";
        }

        return "<?php\n\n".view('stubs.config_viber_profile', [
            'senderName' => $profile['sender_name'] ?? '',
            'avatarExpr' => $avatarExpr,
            'publicAccountUri' => $profile['public_account_uri'] ?? null,
            'eventTypes' => $profile['event_types'] ?? ['message'],
        ])->render();
    }

    /** Получить пути исходного и целевого файлов аватара или null если аватара нет.
     * @param Bot $bot
     * @return array{source_absolute_path: string, zip_relative_path: string}|null
     */
    public function resolveAvatar(Bot $bot): ?array
    {
        $relativePath = $bot->messenger_config['viber']['profile']['avatar_path'] ?? null;
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
            'zip_relative_path' => "storage/app/public/viber-avatar.{$extension}",
        ];
    }
}