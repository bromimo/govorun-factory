<?php

use Illuminate\Http\UploadedFile;
use App\Services\MediaOptimizerService;

test('rejects file over 50 MB', function () {
    $file = UploadedFile::fake()->create('big.mp4', 51 * 1024, 'video/mp4');

    expect(fn () => (new MediaOptimizerService)->optimize($file, 'video'))
        ->toThrow(InvalidArgumentException::class, '50 МБ');
});

test('converts PNG photo to JPEG', function () {
    $file = UploadedFile::fake()->image('test.png', 100, 100);

    $result = (new MediaOptimizerService)->optimize($file, 'photo');

    expect($result['mime_type'])->toBe('image/jpeg');
    expect($result['filename'])->toEndWith('.jpg');
    expect($result['width'])->toBe(100);
    expect($result['height'])->toBe(100);
    expect(file_exists($result['tmp_path']))->toBeTrue();

    @unlink($result['tmp_path']);
});

test('resizes photo larger than 5000px proportionally', function () {
    $file = UploadedFile::fake()->image('big.jpg', 6000, 3000);

    $result = (new MediaOptimizerService)->optimize($file, 'photo');

    expect($result['width'])->toBeLessThanOrEqual(5000);
    expect($result['height'])->toBeLessThanOrEqual(5000);
    expect(abs($result['width'] / $result['height'] - 2.0))->toBeLessThan(0.1);

    @unlink($result['tmp_path']);
});

test('does not resize photo within 5000px limit', function () {
    $file = UploadedFile::fake()->image('normal.jpg', 1920, 1080);

    $result = (new MediaOptimizerService)->optimize($file, 'photo');

    expect($result['width'])->toBe(1920);
    expect($result['height'])->toBe(1080);

    @unlink($result['tmp_path']);
});

test('passes video through without processing', function () {
    $file = UploadedFile::fake()->create('video.mp4', 512, 'video/mp4');

    $result = (new MediaOptimizerService)->optimize($file, 'video');

    expect($result['width'])->toBeNull();
    expect($result['height'])->toBeNull();
    expect(file_exists($result['tmp_path']))->toBeTrue();

    @unlink($result['tmp_path']);
});
