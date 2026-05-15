<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\BotMedia;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\MediaOptimizerService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Управление медиатекой бота. */
class BotMediaController extends Controller
{
    public function __construct(private readonly MediaOptimizerService $optimizer) {}

    /** Список медиафайлов бота.
     *
     */
    public function index(Request $request, Bot $bot): Response|JsonResponse
    {
        $this->authorize('view', $bot);

        $items = $bot->media()->latest()->get()->map(fn ($m) => $this->serialize($bot, $m));

        if ($request->wantsJson()) {
            return response()->json($items);
        }

        return Inertia::render('Bots/Media/Index', [
            'bot' => $bot,
            'media' => $items,
        ]);
    }

    /** Загрузить новый медиафайл в библиотеку бота.
     *
     */
    public function store(Request $request, Bot $bot): JsonResponse
    {
        $this->authorize('update', $bot);

        $request->validate([
            'file' => ['required', 'file', 'max:51200'],
            'type' => ['required', 'in:photo,video,audio,document,animation'],
        ]);

        $optimized = $this->optimizer->optimize($request->file('file'), $request->type);

        Storage::disk('local')->put(
            "media/{$bot->id}/{$optimized['filename']}",
            file_get_contents($optimized['tmp_path'])
        );
        @unlink($optimized['tmp_path']);

        $media = $bot->media()->create([
            'type' => $request->type,
            'original_name' => $request->file('file')->getClientOriginalName(),
            'filename' => $optimized['filename'],
            'mime_type' => $optimized['mime_type'],
            'size' => $optimized['size'],
            'width' => $optimized['width'],
            'height' => $optimized['height'],
        ]);

        return response()->json($this->serialize($bot, $media), 201);
    }

    /** Отдать файл из библиотеки клиенту.
     *
     */
    public function file(Bot $bot, BotMedia $media): StreamedResponse
    {
        $this->authorize('view', $bot);
        abort_if($media->bot_id !== $bot->id, 404);

        $path = "media/{$bot->id}/{$media->filename}";
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, $media->original_name);
    }

    /** Удалить медиафайл из библиотеки и с диска.
     *
     */
    public function destroy(Bot $bot, BotMedia $media): JsonResponse
    {
        $this->authorize('update', $bot);
        abort_if($media->bot_id !== $bot->id, 404);

        $path = "media/{$bot->id}/{$media->filename}";

        if (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }

        $media->delete();

        return response()->json(['deleted' => true]);
    }

    /** Сериализовать медиафайл в массив для API-ответа.
     *
     * @return array<string, mixed>
     */
    private function serialize(Bot $bot, BotMedia $media): array
    {
        return [
            'id' => $media->id,
            'type' => $media->type,
            'original_name' => $media->original_name,
            'filename' => $media->filename,
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'width' => $media->width,
            'height' => $media->height,
            'file_url' => route('bots.media.file', [$bot, $media]),
        ];
    }
}
