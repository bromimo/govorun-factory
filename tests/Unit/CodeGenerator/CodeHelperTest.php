<?php

use App\Services\CodeGenerator\CodeHelper;

test('renderText без плейсчолдеров возвращает одиночный литерал', function () {
    expect(CodeHelper::renderText('Hello'))->toBe("'Hello'");
});

test('renderText интерполирует {{var}} в $this->state->get(...)', function () {
    expect(CodeHelper::renderText('Hi {{name}}!'))
        ->toBe("'Hi ' . \$this->state->get('name') . '!'");
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
