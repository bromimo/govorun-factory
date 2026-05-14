<?php

use App\Services\CodeGenerator\CodeHelper;

test('renderText без плейсчолдеров возвращает одиночный литерал', function () {
    expect(CodeHelper::renderText('Hello'))->toBe("'Hello'");
});

test('renderText интерполирует {{var}} в $this->state->get(...)', function () {
    expect(CodeHelper::renderText('Hi {{name}}!'))
        ->toBe("'Hi ' . \$this->state->get('name') . '!'");
});

test('renderText интерполирует {{bot.name}} в config("app.name")', function () {
    expect(CodeHelper::renderText('Привет от {{bot.name}}!'))
        ->toBe("'Привет от ' . config('app.name') . '!'");
});

test('renderText интерполирует {{bot.name}} в одиночной позиции', function () {
    expect(CodeHelper::renderText('{{bot.name}}'))
        ->toBe("config('app.name')");
});

test('renderText интерполирует {{bot.username}} в config("app.username")', function () {
    expect(CodeHelper::renderText('Пиши мне: {{bot.username}}'))
        ->toBe("'Пиши мне: ' . config('app.username')");
});

test('renderText интерполирует {{bot.username}} в одиночной позиции', function () {
    expect(CodeHelper::renderText('{{bot.username}}'))
        ->toBe("config('app.username')");
});

test('wrapLongLines не трогает строки короче порога', function () {
    $code = "        \$this->reply('short');";
    expect(CodeHelper::wrapLongLines($code))->toBe($code);
});

test('wrapLongLines разбивает длинный литерал по пробелу', function () {
    $text = str_repeat('слово ', 30); // ~6 * 30 = 180 символов
    $line = "        \$this->reply('".trim($text)."');";
    expect(strlen($line))->toBeGreaterThan(120);

    $result = CodeHelper::wrapLongLines($line);

    expect($result)->toContain("        \$this->reply(\n");
    expect($result)->toContain("\n        );");
    foreach (explode("\n", $result) as $l) {
        expect(mb_strlen($l))->toBeLessThanOrEqual(120);
    }
});

test('wrapLongLines разбивает по оператору конкатенации на верхнем уровне', function () {
    $long = str_repeat('x', 50);
    $line = "        \$this->reply('{$long}' . \$this->state->get('name') . '{$long}');";
    expect(strlen($line))->toBeGreaterThan(120);

    $result = CodeHelper::wrapLongLines($line);

    expect($result)->toContain("        \$this->reply(\n");
    expect($result)->toContain("\n            . \$this->state->get('name')\n");
    expect($result)->toContain("\n        );");
});

test('wrapLongLines не разворачивает IIFE без конкатенации и без чистого литерала', function () {
    $line = "            'y' => (function () { \$this->state->set('confirmed', \$message->action); \$this->reply('привет, пользователь'); })(),";
    expect(strlen($line))->toBeGreaterThan(120);

    expect(CodeHelper::wrapLongLines($line))->toBe($line);
});

test('wrapLongLines сохраняет отступ исходной строки', function () {
    $text = str_repeat('слово ', 30);
    $line = "                \$this->reply('".trim($text)."');";

    $result = CodeHelper::wrapLongLines($line);

    $lines = explode("\n", $result);
    expect($lines[0])->toStartWith('                $this->reply(');
    expect($lines[count($lines) - 1])->toBe('                );');
    foreach ($lines as $l) {
        if ($l === '') {
            continue;
        }
        expect($l)->toStartWith('                ');
    }
});

test('wrapLongLines не режет посреди экранирования addslashes', function () {
    $content = str_repeat('a', 60)."\\'".str_repeat('b', 60);
    $line = "        \$this->reply('{$content}');";

    $result = CodeHelper::wrapLongLines($line);

    $tmp = tempnam(sys_get_temp_dir(), 'wrap_test_').'.php';
    file_put_contents($tmp, "<?php\nclass X { public function reply(\$s) {} public function run() {\n{$result}\n} }\n");
    exec('php -l '.escapeshellarg($tmp).' 2>&1', $out, $exit);
    unlink($tmp);

    expect($exit)->toBe(0, 'php -l failed: '.implode("\n", $out)."\nresult:\n{$result}");
});

test('wrapLongLines обрабатывает многострочный код: трогает только длинные строки', function () {
    $short = '        $a = 1;';
    $long = "        \$this->reply('".str_repeat('x ', 80)."');";

    $input = implode("\n", [$short, $long, $short]);

    $output = CodeHelper::wrapLongLines($input);
    $outLines = explode("\n", $output);

    expect($outLines[0])->toBe($short);
    expect($outLines[count($outLines) - 1])->toBe($short);
});

test('renderButton type=action без param', function () {
    $btn = ['type' => 'action', 'label' => 'Маникюр', 'action' => 'manicure'];
    expect(CodeHelper::renderButton($btn))->toBe("Button::make('Маникюр')->action('manicure')");
});

test('renderButton type=action с param', function () {
    $btn = [
        'type' => 'action',
        'label' => 'Выбрать',
        'action' => 'pick',
        'param' => ['id' => 42, 'name' => 'test'],
    ];
    expect(CodeHelper::renderButton($btn))
        ->toBe("Button::make('Выбрать')->action('pick', ['id' => 42, 'name' => 'test'])");
});

test('renderButton type=url', function () {
    $btn = ['type' => 'url', 'label' => 'Сайт', 'url' => 'https://example.com'];
    expect(CodeHelper::renderButton($btn))
        ->toBe("Button::make('Сайт')->url('https://example.com')");
});

test('renderButton type=contact', function () {
    $btn = ['type' => 'contact', 'label' => 'Поделиться номером'];
    expect(CodeHelper::renderButton($btn))
        ->toBe("Button::make('Поделиться номером')->requestContact()");
});

test('renderButton type=location', function () {
    $btn = ['type' => 'location', 'label' => 'Прислать геопозицию'];
    expect(CodeHelper::renderButton($btn))
        ->toBe("Button::make('Прислать геопозицию')->requestLocation()");
});

test('renderButton экранирует одинарные кавычки в label', function () {
    $btn = ['type' => 'action', 'label' => "It's me", 'action' => 'me'];
    expect(CodeHelper::renderButton($btn))
        ->toBe("Button::make('It\\'s me')->action('me')");
});

test('renderButton type по умолчанию action', function () {
    $btn = ['label' => 'Да', 'action' => 'yes'];
    expect(CodeHelper::renderButton($btn))->toBe("Button::make('Да')->action('yes')");
});

test('controllerSubdir returns plural studly for non-fallback types', function () {
    expect(\App\Services\CodeGenerator\CodeHelper::controllerSubdir('command'))->toBe('Commands');
    expect(\App\Services\CodeGenerator\CodeHelper::controllerSubdir('phrase'))->toBe('Phrases');
    expect(\App\Services\CodeGenerator\CodeHelper::controllerSubdir('pattern'))->toBe('Patterns');
    expect(\App\Services\CodeGenerator\CodeHelper::controllerSubdir('action'))->toBe('Actions');
    expect(\App\Services\CodeGenerator\CodeHelper::controllerSubdir('event'))->toBe('Events');
    expect(\App\Services\CodeGenerator\CodeHelper::controllerSubdir('media'))->toBe('Media');
    expect(\App\Services\CodeGenerator\CodeHelper::controllerSubdir('location'))->toBe('Locations');
    expect(\App\Services\CodeGenerator\CodeHelper::controllerSubdir('contact'))->toBe('Contacts');
    expect(\App\Services\CodeGenerator\CodeHelper::controllerSubdir('referral'))->toBe('Referrals');
});

test('controllerSubdir returns null for fallback', function () {
    expect(\App\Services\CodeGenerator\CodeHelper::controllerSubdir('fallback'))->toBeNull();
});

test('controllerNamespace returns subdirectory namespace for non-fallback', function () {
    expect(\App\Services\CodeGenerator\CodeHelper::controllerNamespace('command'))->toBe('App\\Controllers\\Commands');
    expect(\App\Services\CodeGenerator\CodeHelper::controllerNamespace('media'))->toBe('App\\Controllers\\Media');
});

test('controllerNamespace returns root namespace for fallback', function () {
    expect(\App\Services\CodeGenerator\CodeHelper::controllerNamespace('fallback'))->toBe('App\\Controllers');
});
