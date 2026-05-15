<?php

namespace App\Services;

use ZipArchive;
use App\Models\Bot;
use App\Models\BotMedia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use App\Services\CodeGenerator\CodeGeneratorService;

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
            $this->copyMediaFiles($bot, $projectDir);

            $zipName = Str::slug($bot->name).'-'.now()->format('Ymd-His').'.zip';
            $zipPath = storage_path("exports/{$zipName}");
            File::ensureDirectoryExists(storage_path('exports'));

            $this->createZip($projectDir, $zipPath);

            return ['success' => true, 'path' => $zipPath, 'filename' => $zipName];
        } finally {
            File::deleteDirectory($tmpDir);
        }
    }

    /** Скопировать медиафайлы библиотеки бота в директорию ресурсов проекта. */
    private function copyMediaFiles(Bot $bot, string $projectDir): void
    {
        $bot->loadMissing('flows');

        $mediaIds = [];

        foreach ($bot->flows as $flow) {
            foreach ($flow->graph['nodes'] ?? [] as $node) {
                $mediaId = data_get($node, 'data.media.media_id');

                if ($mediaId) {
                    $mediaIds[] = (int) $mediaId;
                }
            }
        }

        if (empty($mediaIds)) {
            return;
        }

        $mediaDir = "{$projectDir}/resources/media";
        File::ensureDirectoryExists($mediaDir);

        BotMedia::whereIn('id', array_unique($mediaIds))->each(
            function (BotMedia $item) use ($bot, $mediaDir) {
                $src = Storage::disk('local')->path("media/{$bot->id}/{$item->filename}");

                if (file_exists($src)) {
                    copy($src, $mediaDir.'/'.basename($item->filename));
                }
            }
        );
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
