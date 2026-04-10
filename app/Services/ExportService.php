<?php

namespace App\Services;

use App\Models\Bot;
use App\Services\CodeGenerator\CodeGeneratorService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

/** Сервис экспорта бота в ZIP-архив с готовым проектом. */
class ExportService
{
    public function __construct(
        private SchemaValidator $validator,
        private CodeGeneratorService $generator,
    ) {}

    /** Экспортировать бота в ZIP.
     * @return array{success: bool, errors?: array<string>, path?: string, filename?: string}
     */
    public function export(Bot $bot): array
    {
        $result = $this->validator->validate();
        if (! $result->isValid()) {
            return ['success' => false, 'errors' => $result->errors];
        }

        $tmpDir = storage_path('app/tmp/'.Str::uuid());
        $projectDir = "{$tmpDir}/".Str::slug($bot->name);

        try {
            File::ensureDirectoryExists($projectDir);
            $this->generator->generate($bot, $projectDir);

            $zipName = Str::slug($bot->name).'-'.now()->format('Ymd-His').'.zip';
            $zipPath = storage_path("exports/{$zipName}");
            File::ensureDirectoryExists(storage_path('exports'));

            $this->createZip($projectDir, $zipPath);

            return ['success' => true, 'path' => $zipPath, 'filename' => $zipName];
        } finally {
            File::deleteDirectory($tmpDir);
        }
    }

    /** Создать ZIP-архив из директории.
     */
    private function createZip(string $sourceDir, string $zipPath): void
    {
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $baseName = basename($sourceDir);
        $files = File::allFiles($sourceDir, true);

        foreach ($files as $file) {
            $relativePath = $baseName.'/'.str_replace('\\', '/', $file->getRelativePathname());
            $zip->addFile($file->getRealPath(), $relativePath);
        }

        $zip->close();
    }
}
