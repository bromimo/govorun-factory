<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use App\Services\ExportService;
use App\Services\SchemaValidator;
use App\Services\CodeGenerator\CodeGeneratorService;

/** Контроллер экспорта бота в ZIP-архив. */
class ExportController extends Controller
{
    /** Время жизни архива в секундах после которого он считается устаревшим. */
    private const STALE_THRESHOLD_SECONDS = 600;

    /** Экспортировать бота.
     */
    public function __invoke(Bot $bot)
    {
        $this->authorize('export', $bot);

        $this->cleanupStaleArchives();

        $service = new ExportService(
            new SchemaValidator($bot),
            new CodeGeneratorService,
        );

        $result = $service->export($bot);

        if (! $result['success']) {
            return response()->json(['errors' => $result['errors']], 422);
        }

        $path = $result['path'];
        $size = (string) filesize($path);

        // Скрипт продолжает работать после обрыва соединения, чтобы finally
        // в стриме гарантированно успел unlink. Без этого на Windows + artisan
        // serve clien-abort убивал PHP до выполнения cleanup.
        ignore_user_abort(true);

        return response()->streamDownload(function () use ($path) {
            try {
                readfile($path);
            } finally {
                @unlink($path);
            }
        }, $result['filename'], [
            'Content-Type' => 'application/zip',
            'Content-Length' => $size,
        ]);
    }

    /** Удалить устаревшие zip-архивы из storage/exports.
     * Защита-в-глубину: если предыдущая загрузка прервалась и finally
     * не успел сработать, файл будет удалён при следующем экспорте.
     * @return void
     */
    private function cleanupStaleArchives(): void
    {
        $dir = storage_path('exports');

        if (! is_dir($dir)) {
            return;
        }

        $threshold = time() - self::STALE_THRESHOLD_SECONDS;
        $files = glob($dir.DIRECTORY_SEPARATOR.'*.zip') ?: [];

        foreach ($files as $file) {
            if (@filemtime($file) < $threshold) {
                @unlink($file);
            }
        }
    }
}
