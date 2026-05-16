<?php

use App\Services\Http\SsrfGuard;

it('блокирует приватные IPv4 сети', function () {
    $guard = new SsrfGuard;

    expect($guard->isAllowed('http://127.0.0.1/x'))->toBeFalse();
    expect($guard->isAllowed('http://10.0.5.1/x'))->toBeFalse();
    expect($guard->isAllowed('http://192.168.1.1/x'))->toBeFalse();
    expect($guard->isAllowed('http://172.16.0.1/x'))->toBeFalse();
    expect($guard->isAllowed('http://169.254.169.254/x'))->toBeFalse();
});

it('разрешает публичные адреса', function () {
    $guard = new SsrfGuard;

    expect($guard->isAllowed('https://api.github.com/'))->toBeTrue();
    expect($guard->isAllowed('https://8.8.8.8/x'))->toBeTrue();
});

it('блокирует IPv6 loopback и link-local', function () {
    $guard = new SsrfGuard;

    expect($guard->isAllowed('http://[::1]/x'))->toBeFalse();
    expect($guard->isAllowed('http://[fe80::1]/x'))->toBeFalse();
});

it('блокирует невалидные URL', function () {
    $guard = new SsrfGuard;

    expect($guard->isAllowed('not-a-url'))->toBeFalse();
    expect($guard->isAllowed('file:///etc/passwd'))->toBeFalse();
});
