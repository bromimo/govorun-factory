<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

/** Конвертирует видео под требования Telegram setMyProfilePhoto: 640×640, ≤5 сек, H.264, без аудио. */
class TelegramProfileVideoConverter
{
    public function __construct(
        private readonly string $ffmpegBinary = 'ffmpeg',
        private readonly int $timeoutSeconds = 60,
    ) {}

    /** Конвертировать видео в файл, совместимый с Bot API setMyProfilePhoto.
     * @param string $sourcePath Полный путь к исходному файлу.
     * @param string $destinationPath Полный путь, куда писать сконвертированный mp4.
     * @return void
     * @throws \RuntimeException Если ffmpeg недоступен или конвертация провалилась.
     */
    public function convert(string $sourcePath, string $destinationPath): void
    {
        $process = new Process([
            $this->ffmpegBinary,
            '-y',
            '-i', $sourcePath,
            '-vf', 'scale=640:640:force_original_aspect_ratio=increase,crop=640:640',
            '-t', '5',
            '-an',
            '-r', '30',
            '-c:v', 'libx264',
            '-pix_fmt', 'yuv420p',
            '-movflags', '+faststart',
            $destinationPath,
        ]);

        $process->setTimeout($this->timeoutSeconds);

        try {
            $process->mustRun();
        } catch (ProcessTimedOutException) {
            throw new \RuntimeException('Конвертация видео превысила лимит времени.');
        } catch (ProcessFailedException $e) {
            $stderr = trim($process->getErrorOutput());
            $hint = $this->guessHint($stderr);
            throw new \RuntimeException("ffmpeg не смог обработать видео.{$hint}\n\nПодробности: {$stderr}", 0, $e);
        }
    }

    /** Подсказка по типичным причинам сбоя ffmpeg.
     * @param string $stderr Сырой stderr ffmpeg.
     * @return string Префикс с подсказкой или пустая строка.
     */
    private function guessHint(string $stderr): string
    {
        if (str_contains($stderr, 'No such file') || str_contains($stderr, 'not found')) {
            return ' Проверьте, что ffmpeg установлен в системе.';
        }

        if (str_contains($stderr, 'Invalid data') || str_contains($stderr, 'moov atom not found')) {
            return ' Файл повреждён или это не валидное видео.';
        }

        return '';
    }
}
