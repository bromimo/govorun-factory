<?php

use App\Services\Http\TemplatePlaceholderRenderer;

it('подставляет state.* плейсхолдеры', function () {
    $r = new TemplatePlaceholderRenderer;

    expect($r->render('Привет, {{state.name}}!', ['name' => 'Иван']))
        ->toBe('Привет, Иван!');
});

it('заменяет на пустую строку, если ключа нет', function () {
    $r = new TemplatePlaceholderRenderer;

    expect($r->render('a={{state.x}}b', []))->toBe('a=b');
});

it('сохраняет другие двойные фигурные скобки', function () {
    $r = new TemplatePlaceholderRenderer;

    expect($r->render('{{notstate.x}}', ['x' => 'v']))->toBe('{{notstate.x}}');
});
