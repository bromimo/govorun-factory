<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use App\Services\ExportService;
use App\Services\SchemaValidator;
use App\Services\CodeGenerator\CodeGeneratorService;

/** Контроллер экспорта бота в ZIP-архив. */
class ExportController extends Controller
{
    /** Экспортировать бота.
     */
    public function __invoke(Bot $bot)
    {
        $this->authorize('export', $bot);

        $service = new ExportService(
            new SchemaValidator($bot),
            new CodeGeneratorService,
        );

        $result = $service->export($bot);

        if (! $result['success']) {
            return response()->json(['errors' => $result['errors']], 422);
        }

        $path = $result['path'];

        return response()->streamDownload(function () use ($path) {
            try {
                readfile($path);
            } finally {
                @unlink($path);
            }
        }, $result['filename'], [
            'Content-Type' => 'application/zip',
            'Content-Length' => (string) filesize($path),
        ]);
    }
}
