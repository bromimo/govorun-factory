<?php

use App\Services\TelegramHtml;

test('sanitize passes allowed HTML unchanged', function () {
    $html = '<b>bold</b> and <i>italic</i> and <u>under</u>';
    expect(TelegramHtml::sanitize($html))->toBe($html);
});

test('sanitize strips disallowed tags keeping content', function () {
    expect(TelegramHtml::sanitize('<script>alert(1)</script>text'))->toBe('alert(1)text');
    expect(TelegramHtml::sanitize('<div>hello</div>'))->toBe('hello');
});

test('sanitize strips disallowed attributes', function () {
    expect(TelegramHtml::sanitize('<b onclick="x()">bold</b>'))->toBe('<b>bold</b>');
    expect(TelegramHtml::sanitize('<span class="other">text</span>'))->toBe('text');
});

test('sanitize keeps a href with allowed schemes', function () {
    $html = '<a href="https://example.com">link</a>';
    expect(TelegramHtml::sanitize($html))->toBe($html);

    $tg = '<a href="tg://user?id=123">tg</a>';
    expect(TelegramHtml::sanitize($tg))->toBe($tg);
});

test('sanitize strips a href with javascript scheme', function () {
    $result = TelegramHtml::sanitize('<a href="javascript:alert(1)">x</a>');
    expect($result)->not->toContain('javascript:');
    expect($result)->toContain('x');
});

test('sanitize keeps span tg-spoiler', function () {
    $html = '<span class="tg-spoiler">secret</span>';
    expect(TelegramHtml::sanitize($html))->toBe($html);
});

test('sanitize keeps blockquote expandable attribute', function () {
    $html = '<blockquote expandable="">text</blockquote>';
    expect(TelegramHtml::sanitize($html))->toBe($html);
});

test('sanitize strips blockquote unknown attributes', function () {
    $result = TelegramHtml::sanitize('<blockquote class="x" style="color:red">text</blockquote>');
    expect($result)->toBe('<blockquote>text</blockquote>');
});

test('sanitize result differs from input for invalid HTML', function () {
    $input = '<script>x</script>';
    expect(TelegramHtml::sanitize($input))->not->toBe($input);

    $input2 = '<div>hello</div>';
    expect(TelegramHtml::sanitize($input2))->not->toBe($input2);
});

test('htmlEscapeKeepPlaceholders escapes angle brackets', function () {
    expect(TelegramHtml::htmlEscapeKeepPlaceholders('a<b>{{x}}<c'))
        ->toBe('a&lt;b&gt;{{x}}&lt;c');
});

test('htmlEscapeKeepPlaceholders preserves placeholders unchanged', function () {
    $text = 'Hi {{user.firstName}}, you are {{age}} years old';
    $result = TelegramHtml::htmlEscapeKeepPlaceholders($text);
    expect($result)->toContain('{{user.firstName}}');
    expect($result)->toContain('{{age}}');
    expect($result)->not->toContain('&lt;');
});

test('htmlEscapeKeepPlaceholders escapes ampersand outside placeholders', function () {
    expect(TelegramHtml::htmlEscapeKeepPlaceholders('AT&T {{name}}'))
        ->toBe('AT&amp;T {{name}}');
});

test('htmlEscapeKeepPlaceholders handles empty string', function () {
    expect(TelegramHtml::htmlEscapeKeepPlaceholders(''))->toBe('');
});

test('sanitize strips href with relative url keeping tag', function () {
    $result = TelegramHtml::sanitize('<a href="/relative/path">link</a>');
    expect($result)->toBe('<a>link</a>');
});
