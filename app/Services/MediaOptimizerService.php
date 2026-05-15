<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

/** Оптимизирует медиафайлы для Telegram перед сохранением. */
class MediaOptimizerService
{
    private const MAX_SIZE = 50 * 1024 * 1024;

    private const MAX_DIM = 5000;

    private const JPEG_QUALITY = 85;

    /** Оптимизировать файл под тип медиа.
     *
     * @param  UploadedFile  $file
     * @param  string  $type  photo|video|audio|document|animation
     * @return array{tmp_path: string, filename: string, mime_type: string, size: int, width: int|null, height: int|null}
     *
     * @throws InvalidArgumentException
     */
    public function optimize(UploadedFile $file, string $type): array
    {
        if ($file->getSize() > self::MAX_SIZE) {
            throw new InvalidArgumentException('Файл превышает максимальный размер 50 МБ.');
        }

        if ($type === 'photo') {
            return $this->optimizePhoto($file);
        }

        if ($type === 'animation' && extension_loaded('imagick')) {
            return $this->optimizeAnimationImagick($file);
        }

        return $this->passThrough($file);
    }

    /** @return array{tmp_path: string, filename: string, mime_type: string, size: int, width: int, height: int} */
    private function optimizePhoto(UploadedFile $file): array
    {
        $mime = $file->getMimeType() ?? '';

        // HEIC/HEIF поддерживается только через Imagick
        if (str_contains($mime, 'heic') || str_contains($mime, 'heif')) {
            if (! extension_loaded('imagick')) {
                throw new InvalidArgumentException('HEIC/HEIF формат требует расширение Imagick.');
            }

            return $this->optimizeImageWithImagick($file);
        }

        $image = match (true) {
            str_contains($mime, 'jpeg') || str_contains($mime, 'jpg') => imagecreatefromjpeg($file->getRealPath()),
            str_contains($mime, 'png') => $this->pngToTruecolor($file->getRealPath()),
            str_contains($mime, 'webp') => imagecreatefromwebp($file->getRealPath()),
            str_contains($mime, 'gif') => imagecreatefromgif($file->getRealPath()),
            default => false,
        };

        if (! $image) {
            throw new InvalidArgumentException('Не удалось обработать изображение.');
        }

        $origW = imagesx($image);
        $origH = imagesy($image);
        [$newW, $newH] = $this->scaledDimensions($origW, $origH);

        if ($newW !== $origW || $newH !== $origH) {
            $scaled = imagescale($image, $newW, $newH);
            imagedestroy($image);
            $image = $scaled;
        }

        $filename = uniqid('media_', true) . '.jpg';
        $tmpPath = sys_get_temp_dir() . '/' . $filename;
        imagejpeg($image, $tmpPath, self::JPEG_QUALITY);
        imagedestroy($image);

        return [
            'tmp_path'  => $tmpPath,
            'filename'  => $filename,
            'mime_type' => 'image/jpeg',
            'size'      => filesize($tmpPath),
            'width'     => $newW,
            'height'    => $newH,
        ];
    }

    private function pngToTruecolor(string $path): \GdImage|false
    {
        $src = imagecreatefrompng($path);

        if (! $src) {
            return false;
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $dst = imagecreatetruecolor($w, $h);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);
        imagecopy($dst, $src, 0, 0, 0, 0, $w, $h);
        imagedestroy($src);

        return $dst;
    }

    /** @return array{tmp_path: string, filename: string, mime_type: string, size: int, width: int, height: int} */
    private function optimizeImageWithImagick(UploadedFile $file): array
    {
        $imagick = new \Imagick($file->getRealPath() . '[0]');
        $imagick->setImageFormat('jpeg');
        $imagick->setImageCompressionQuality(self::JPEG_QUALITY);

        $origW = $imagick->getImageWidth();
        $origH = $imagick->getImageHeight();
        [$newW, $newH] = $this->scaledDimensions($origW, $origH);

        if ($newW !== $origW || $newH !== $origH) {
            $imagick->resizeImage($newW, $newH, \Imagick::FILTER_LANCZOS, 1);
        }

        $filename = uniqid('media_', true) . '.jpg';
        $tmpPath = sys_get_temp_dir() . '/' . $filename;
        $imagick->writeImage($tmpPath);
        $imagick->destroy();

        return [
            'tmp_path'  => $tmpPath,
            'filename'  => $filename,
            'mime_type' => 'image/jpeg',
            'size'      => filesize($tmpPath),
            'width'     => $newW,
            'height'    => $newH,
        ];
    }

    /** @return array{tmp_path: string, filename: string, mime_type: string, size: int, width: int, height: int} */
    private function optimizeAnimationImagick(UploadedFile $file): array
    {
        $imagick = new \Imagick($file->getRealPath());
        $imagick = $imagick->coalesceImages();
        $imagick->setFormat('gif');

        $filename = uniqid('media_', true) . '.gif';
        $tmpPath = sys_get_temp_dir() . '/' . $filename;
        $imagick->writeImages($tmpPath, true);

        $w = $imagick->getImageWidth();
        $h = $imagick->getImageHeight();
        $imagick->destroy();

        return [
            'tmp_path'  => $tmpPath,
            'filename'  => $filename,
            'mime_type' => 'image/gif',
            'size'      => filesize($tmpPath),
            'width'     => $w,
            'height'    => $h,
        ];
    }

    /** @return array{tmp_path: string, filename: string, mime_type: string, size: int, width: null, height: null} */
    private function passThrough(UploadedFile $file): array
    {
        $ext = $file->getClientOriginalExtension() ?: 'bin';
        $filename = uniqid('media_', true) . '.' . $ext;
        $tmpPath = sys_get_temp_dir() . '/' . $filename;
        copy($file->getRealPath(), $tmpPath);

        return [
            'tmp_path'  => $tmpPath,
            'filename'  => $filename,
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size'      => filesize($tmpPath),
            'width'     => null,
            'height'    => null,
        ];
    }

    /** @return array{int, int} */
    private function scaledDimensions(int $w, int $h): array
    {
        if ($w <= self::MAX_DIM && $h <= self::MAX_DIM) {
            return [$w, $h];
        }

        $ratio = min(self::MAX_DIM / $w, self::MAX_DIM / $h);

        return [(int) round($w * $ratio), (int) round($h * $ratio)];
    }
}
