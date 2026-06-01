<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use App\Services\MediaOptimizerService;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\UploadWhatsAppPhotoRequest;
use App\Http\Requests\UpdateWhatsAppProfileRequest;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Контроллер страницы настроек бизнес-профиля WhatsApp. */
class WhatsAppProfileController extends Controller
{
    /** Показать страницу профиля.
     * @param  Bot  $bot  Бот
     * @return Response Inertia-страница
     */
    public function edit(Bot $bot): Response
    {
        $this->authorize('view', $bot);

        return Inertia::render('Bots/WhatsAppProfile', [
            'bot' => $bot,
            'can' => ['update' => request()->user()->can('update', $bot)],
        ]);
    }

    /** Сохранить поля профиля.
     * @param  UpdateWhatsAppProfileRequest  $request  Запрос
     * @param  Bot  $bot  Бот
     * @return RedirectResponse Назад
     */
    public function update(UpdateWhatsAppProfileRequest $request, Bot $bot): RedirectResponse
    {
        $config = $bot->messenger_config ?? [];
        $config['whatsapp'] = $config['whatsapp'] ?? ['enabled' => true];
        $existing = $config['whatsapp']['profile'] ?? [];
        $config['whatsapp']['profile'] = array_merge($existing, $request->validated()['profile']);
        $bot->messenger_config = $config;
        $bot->save();

        return back();
    }

    /** Загрузить фото профиля.
     * @param  UploadWhatsAppPhotoRequest  $request  Запрос
     * @param  Bot  $bot  Бот
     * @param  MediaOptimizerService  $optimizer  Оптимизатор
     * @return RedirectResponse На страницу профиля
     */
    public function uploadPhoto(
        UploadWhatsAppPhotoRequest $request,
        Bot $bot,
        MediaOptimizerService $optimizer,
    ): RedirectResponse {
        $file = $request->file('file');
        $disk = Storage::disk('local');
        $dir = "bots/{$bot->id}/whatsapp";

        foreach (['jpg', 'png'] as $ext) {
            $disk->delete("{$dir}/profile.{$ext}");
        }

        $relativePath = "{$dir}/profile.jpg";
        $optimized = $optimizer->optimize($file, 'photo');
        $disk->put($relativePath, file_get_contents($optimized['tmp_path']));
        @unlink($optimized['tmp_path']);

        $config = $bot->messenger_config ?? [];
        $config['whatsapp'] = $config['whatsapp'] ?? ['enabled' => true];
        $config['whatsapp']['profile']['photo_path'] = $relativePath;
        $bot->messenger_config = $config;
        $bot->save();

        return redirect()->route('bots.whatsapp.profile.edit', $bot);
    }

    /** Удалить фото профиля.
     * @param  Bot  $bot  Бот
     * @return RedirectResponse На страницу профиля
     */
    public function deletePhoto(Bot $bot): RedirectResponse
    {
        $this->authorize('update', $bot);

        $config = $bot->messenger_config ?? [];
        $relativePath = $config['whatsapp']['profile']['photo_path'] ?? null;

        if ($relativePath !== null) {
            Storage::disk('local')->delete($relativePath);
            $config['whatsapp']['profile']['photo_path'] = null;
            $bot->messenger_config = $config;
            $bot->save();
        }

        return redirect()->route('bots.whatsapp.profile.edit', $bot);
    }

    /** Отдать файл фото профиля.
     * @param  Bot  $bot  Бот
     * @return BinaryFileResponse|\Illuminate\Http\Response Файл или 404
     */
    public function showPhoto(Bot $bot): BinaryFileResponse|\Illuminate\Http\Response
    {
        $this->authorize('view', $bot);

        $relativePath = $bot->messenger_config['whatsapp']['profile']['photo_path'] ?? null;

        if ($relativePath === null) {
            return response()->noContent(404);
        }

        $absolute = Storage::disk('local')->path($relativePath);

        if (! is_file($absolute)) {
            return response()->noContent(404);
        }

        return response()->file($absolute, ['Content-Type' => 'image/jpeg']);
    }
}
