<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\TelegramProfileVideoConverter;
use Symfony\Component\Process\ExecutableFinder;

/** Тесты конвертера видео под Telegram-профиль. */
class TelegramProfileVideoConverterTest extends TestCase
{
    public function test_throws_when_ffmpeg_binary_missing(): void
    {
        $converter = new TelegramProfileVideoConverter('ffmpeg-does-not-exist-anywhere');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ffmpeg не смог обработать видео.');

        $converter->convert('/tmp/whatever.mp4', '/tmp/out.mp4');
    }

    public function test_converts_real_mp4_when_ffmpeg_available(): void
    {
        $ffmpeg = (new ExecutableFinder)->find('ffmpeg');
        if ($ffmpeg === null) {
            $this->markTestSkipped('ffmpeg не установлен в окружении.');
        }

        $src = tempnam(sys_get_temp_dir(), 'tg-src-').'.mp4';
        $dest = tempnam(sys_get_temp_dir(), 'tg-dst-').'.mp4';

        $genCmd = sprintf(
            '%s -y -f lavfi -i color=c=red:s=320x240:d=2 -c:v libx264 -pix_fmt yuv420p %s 2>&1',
            escapeshellarg($ffmpeg),
            escapeshellarg($src),
        );
        exec($genCmd, $genOut, $genCode);
        $this->assertSame(0, $genCode, 'не удалось сгенерировать тестовый mp4: '.implode("\n", $genOut));

        try {
            (new TelegramProfileVideoConverter($ffmpeg))->convert($src, $dest);

            $this->assertFileExists($dest);
            $this->assertGreaterThan(0, filesize($dest));
        } finally {
            @unlink($src);
            @unlink($dest);
        }
    }
}
