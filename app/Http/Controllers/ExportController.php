<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use App\Services\CodeGenerator\CodeGeneratorService;
use App\Services\ExportService;
use App\Services\SchemaValidator;

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

        return response()->download($result['path'], $result['filename'], [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend();
    }
}
