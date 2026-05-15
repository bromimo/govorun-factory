<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\StoreBotRequest;
use App\Http\Requests\UpdateBotRequest;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\UploadBotPhotoRequest;
use Illuminate\Validation\ValidationException;
use App\Services\TelegramProfileVideoConverter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BotController extends Controller
{
    /** Список ботов с поиском.
     * @return Response
     */
    public function index(Request $request)
    {
        $bots = Bot::query()
            ->with('updater:id,name')
            ->withCount(['routes', 'flows'])
            ->when($request->search, fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->latest('updated_at')
            ->get();

        return Inertia::render('Dashboard/Index', [
            'bots' => $bots,
            'filters' => ['search' => $request->search],
            'can' => [
                'createBot' => $request->user()->can('create', Bot::class),
            ],
        ]);
    }

    /** Создание нового бота.
     * @return RedirectResponse
     */
    public function store(StoreBotRequest $request)
    {
        $bot = Bot::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('bots.edit', $bot);
    }

    /** Страница редактора бота.
     * @return Response
     */
    public function edit(Bot $bot)
    {
        $this->authorize('view', $bot);

        $bot->load(['routes' => fn ($q) => $q->whereNull('parent_id')->orderBy('sort_order')->with('children'), 'flows']);

        return Inertia::render('Bots/Edit', [
            'bot' => $bot,
            'can' => [
                'update' => request()->user()->can('update', $bot),
                'delete' => request()->user()->can('delete', $bot),
                'export' => request()->user()->can('export', $bot),
            ],
        ]);
    }

    /** Обновление настроек бота.
     * @return RedirectResponse
     */
    public function update(UpdateBotRequest $request, Bot $bot)
    {
        $bot->update($request->validated());

        return redirect()->route('bots.edit', $bot);
    }

    /** Удаление бота.
     * @return RedirectResponse
     */
    public function destroy(Bot $bot)
    {
        $this->authorize('delete', $bot);

        $bot->delete();

        return redirect()->route('dashboard');
    }

    /** Загрузить аватар бота: PNG конвертится в JPG, MP4 — нормализуется под спеку Telegram.
     * @throws ValidationException Если конвертация видео провалилась.
     */
    public function uploadProfilePhoto(
        UploadBotPhotoRequest $request,
        Bot $bot,
        TelegramProfileVideoConverter $videoConverter,
    ): RedirectResponse {
        $file = $request->file('file');
        $mime = $file->getMimeType();

        $disk = Storage::disk('local');
        $dir = "bot-profiles/{$bot->id}";

        foreach (['jpg', 'mp4'] as $ext) {
            $disk->delete("{$dir}/profile.{$ext}");
        }

        if ($mime === 'video/mp4') {
            $relativePath = "{$dir}/profile.mp4";
            $tmpDest = tempnam(sys_get_temp_dir(), 'tg-mp4-').'.mp4';

            try {
                $videoConverter->convert($file->getRealPath(), $tmpDest);
                $disk->put($relativePath, file_get_contents($tmpDest));
            } catch (\RuntimeException $e) {
                throw ValidationException::withMessages(['file' => $e->getMessage()]);
            } finally {
                @unlink($tmpDest);
            }
        } else {
            $relativePath = "{$dir}/profile.jpg";
            $jpgBinary = $this->encodeAsJpeg($file->getRealPath(), $mime);
            $disk->put($relativePath, $jpgBinary);
        }

        $config = $bot->messenger_config ?? [];
        $config['telegram']['profile']['photo_path'] = $relativePath;
        $bot->messenger_config = $config;
        $bot->save();

        return redirect()->route('bots.edit', $bot);
    }

    /** Удалить аватар бота: чистит файл и обнуляет photo_path.
     */
    public function deleteProfilePhoto(Bot $bot): RedirectResponse
    {
        $this->authorize('update', $bot);

        $config = $bot->messenger_config ?? [];
        $path = $config['telegram']['profile']['photo_path'] ?? null;

        if ($path !== null) {
            Storage::disk('local')->delete($path);
        }

        $config['telegram']['profile']['photo_path'] = null;
        $bot->messenger_config = $config;
        $bot->save();

        return redirect()->route('bots.edit', $bot);
    }

    /** Отдать аватар бота для превью в админке.
     * @return BinaryFileResponse|\Illuminate\Http\Response
     */
    public function showProfilePhoto(Bot $bot)
    {
        $this->authorize('view', $bot);

        $path = $bot->messenger_config['telegram']['profile']['photo_path'] ?? null;
        if ($path === null) {
            return response()->noContent(404);
        }

        $absolute = Storage::disk('local')->path($path);
        if (! is_file($absolute)) {
            return response()->noContent(404);
        }

        $ext = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));
        $mime = $ext === 'mp4' ? 'video/mp4' : 'image/jpeg';

        return response()->file($absolute, ['Content-Type' => $mime]);
    }

    /** Закодировать изображение как JPEG с помощью GD.
     * @param  string  $path  Полный путь к исходному файлу.
     * @param  string  $mime  MIME исходного файла.
     * @return string Бинарь JPEG.
     *
     * @throws \RuntimeException Если MIME не поддерживается.
     */
    private function encodeAsJpeg(string $path, string $mime): string
    {
        $source = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            default => throw new \RuntimeException("Unsupported MIME for JPEG conversion: {$mime}"),
        };

        ob_start();
        imagejpeg($source, null, 85);
        $binary = ob_get_clean();
        imagedestroy($source);

        return $binary;
    }
}
