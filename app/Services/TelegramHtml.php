<?php

namespace App\Services;

/** Утилиты для работы с Telegram-совместимым HTML. */
class TelegramHtml
{
    private const ALLOWED_TAGS = [
        'b', 'strong', 'i', 'em', 'u', 'ins', 's', 'strike', 'del',
        'code', 'pre', 'a', 'blockquote', 'span',
    ];

    /** Санитизировать HTML до whitelist Telegram.
     * Возвращает строку без изменений, если она уже валидна.
     */
    public static function sanitize(string $html): string
    {
        if ($html === '') {
            return '';
        }

        // Трекаем, сколько открытых <span class="tg-spoiler"> встречено
        // (разрешённых), чтобы не удалить их закрывающие теги
        $allowedSpanDepth = 0;

        return (string) preg_replace_callback(
            '/<(\/?)([a-zA-Z][a-zA-Z0-9]*)([^>]*)>/u',
            function (array $m) use (&$allowedSpanDepth): string {
                $closing = $m[1];
                $tag = strtolower($m[2]);
                $attrs = $m[3];

                if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                    return '';
                }

                // span разрешён только с классом tg-spoiler
                if ($tag === 'span') {
                    if ($closing === '/') {
                        // Закрывающий тег: пропускаем только если есть соответствующий открытый
                        if ($allowedSpanDepth > 0) {
                            $allowedSpanDepth--;

                            return '</span>';
                        }

                        return '';
                    }

                    // Открывающий тег: проверяем атрибуты
                    $filtered = self::filterAttrs($tag, $attrs);
                    if ($filtered === '') {
                        return '';
                    }

                    $allowedSpanDepth++;

                    return "<span{$filtered}>";
                }

                return "<{$closing}{$tag}".self::filterAttrs($tag, $attrs).'>';
            },
            $html
        );
    }

    /** Экранировать спецсимволы HTML, оставляя плейсхолдеры {{...}} нетронутыми.
     * @param  string  $text  Исходный plain-text.
     */
    public static function htmlEscapeKeepPlaceholders(string $text): string
    {
        if ($text === '') {
            return '';
        }

        $parts = preg_split('/(\{\{[^}]+\}\})/', $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?? [];

        return implode('', array_map(function (string $part): string {
            if (str_starts_with($part, '{{') && str_ends_with($part, '}}')) {
                return $part;
            }

            return htmlspecialchars($part, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }, $parts));
    }

    /** Фильтровать атрибуты тега — оставить только разрешённые.
     * @param  string  $tag  Имя тега.
     * @param  string  $raw  Строка атрибутов.
     * @return string Строка разрешённых атрибутов с ведущим пробелом или пустая строка.
     */
    private static function filterAttrs(string $tag, string $raw): string
    {
        if (trim($raw) === '') {
            return '';
        }

        $out = [];

        if ($tag === 'a' && preg_match('/\bhref="([^"]*)"/', $raw, $m)) {
            $scheme = strtolower((string) parse_url($m[1], PHP_URL_SCHEME));
            if (in_array($scheme, ['http', 'https', 'tg'], true)) {
                $out[] = "href=\"{$m[1]}\"";
            }
        }

        if ($tag === 'blockquote' && preg_match('/\bexpandable\b/', $raw)) {
            $out[] = 'expandable=""';
        }

        if ($tag === 'span' && preg_match('/\bclass="([^"]*)"/', $raw, $m)
            && trim($m[1]) === 'tg-spoiler') {
            $out[] = 'class="tg-spoiler"';
        }

        if ($tag === 'code' && preg_match('/\bclass="([^"]*)"/', $raw, $m)
            && str_starts_with($m[1], 'language-')) {
            $out[] = "class=\"{$m[1]}\"";
        }

        return $out ? ' '.implode(' ', $out) : '';
    }
}
