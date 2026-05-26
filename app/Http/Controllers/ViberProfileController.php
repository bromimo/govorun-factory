<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use App\Services\MediaOptimizerService;
use App\Http\Requests\UpdateViberProfileRequest;
use App\Http\Requests\UploadViberAvatarRequest;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Контроллер страницы настроек профиля Viber-бота. */
class ViberProfileController extends Controller
{
    /** Страница настроек профиля Viber-бота. */
    public function edit(Bot $bot): Response
    {
        $this->authorize('view', $bot);

        return Inertia::render('Bots/ViberProfile', [
            'bot' => $bot,
            'can' => [
                'update' => request()->user()->can('update', $bot),
            ],
        ]);
    }

    /** Обновить секцию messenger_config.viber.profile. */
    public function update(UpdateViberProfileRequest $request, Bot $bot): RedirectResponse
    {
        $config = $bot->messenger_config ?? [];
        $config['viber'] = $config['viber'] ?? ['enabled' => true];
        $config['viber']['profile'] = $request->validated()['profile'];

        $bot->messenger_config = $config;
        $bot->save();

        return back();
    }

    /** Загрузить аватар Viber-бота: оптимизирует изображение и сохраняет в storage. */
    public function uploadAvatar(
        UploadViberAvatarRequest $request,
        Bot $bot,
        MediaOptimizerService $optimizer,
    ): RedirectResponse {
        $file = $request->file('file');
        $disk = Storage::disk('local');
        $dir = "bots/{$bot->id}/viber";

        foreach (['jpg', 'png'] as $ext) {
            $disk->delete("{$dir}/avatar.{$ext}");
        }

        $relativePath = "{$dir}/avatar.jpg";
        $optimized = $optimizer->optimize($file, 'photo');
        $disk->put($relativePath, file_get_contents($optimized['tmp_path']));
        @unlink($optimized['tmp_path']);

        $config = $bot->messenger_config ?? [];
        $config['viber']['profile']['avatar_path'] = $relativePath;
        $bot->messenger_config = $config;
        $bot->save();

        return redirect()->route('bots.viber.profile.edit', $bot);
    }

    /** Удалить аватар Viber-бота: чистит файл и обнуляет avatar_path. */
    public function deleteAvatar(Bot $bot): RedirectResponse
    {
        $this->authorize('update', $bot);

        $config = $bot->messenger_config ?? [];
        $path = $config['viber']['profile']['avatar_path'] ?? null;

        if ($path !== null) {
            Storage::disk('local')->delete($path);
        }

        $config['viber']['profile']['avatar_path'] = null;
        $bot->messenger_config = $config;
        $bot->save();

        return redirect()->route('bots.viber.profile.edit', $bot);
    }

    /** Отдать аватар Viber-бота для превью в админке. */
    public function showAvatar(Bot $bot): BinaryFileResponse|\Illuminate\Http\Response
    {
        $this->authorize('view', $bot);

        $path = $bot->messenger_config['viber']['profile']['avatar_path'] ?? null;
        if ($path === null) {
            return response()->noContent(404);
        }

        $absolute = Storage::disk('local')->path($path);
        if (! is_file($absolute)) {
            return response()->noContent(404);
        }

        return response()->file($absolute, ['Content-Type' => 'image/jpeg']);
    }
}