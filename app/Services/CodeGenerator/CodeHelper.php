<?php

namespace App\Services\CodeGenerator;

/** Вспомогательные методы для генерации PHP-кода. */
class CodeHelper
{
    /** Отрендерить текст с интерполяцией переменных.
     * {{var}} → $this->state->get('var')
     * {{user.firstName}} → $this->message->user->firstName
     *
     * @return string PHP-выражение
     */
    public static function renderText(string $text): string
    {
        if (! str_contains($text, '{{')) {
            return "'".addslashes($text)."'";
        }

        $parts = preg_split('/(\{\{\s*[\w.]+\s*\}\})/', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $segments = [];
        foreach ($parts as $part) {
            if (preg_match('/^\{\{\s*([\w.]+)\s*\}\}$/', $part, $m)) {
                $segments[] = self::renderVariable($m[1]);
            } else {
                $segments[] = "'".addslashes($part)."'";
            }
        }

        return implode(' . ', $segments);
    }

    /** Отрендерить переменную в PHP-выражение.
     * Dot-нотация (user.firstName) → $this->message->user->firstName
     * Простое имя (name) → $this->state->get('name')
     *
     * @return string PHP-выражение
     */
    private static function renderVariable(string $variable): string
    {
        if (str_contains($variable, '.')) {
            return '$this->message->'.str_replace('.', '->', $variable);
        }

        return "\$this->state->get('{$variable}')";
    }

    /** Пост-процессор: обернуть строки PHP-кода длиной > $maxWidth символов.
     * Стратегия переноса: сначала по оператору конкатенации « . » на верхнем уровне,
     * иначе по `\n` или ближайшему к порогу пробелу внутри строкового литерала.
     */
    public static function wrapLongLines(string $phpCode, int $maxWidth = 120): string
    {
        $lines = explode("\n", $phpCode);
        $out = [];
        foreach ($lines as $line) {
            if (self::visualLength($line) <= $maxWidth) {
                $out[] = $line;

                continue;
            }
            $wrapped = self::wrapLine($line, $maxWidth);
            foreach (explode("\n", $wrapped) as $w) {
                $out[] = $w;
            }
        }

        return implode("\n", $out);
    }

    /** Обернуть одну длинную строку. Возвращает результат (возможно многострочный).
     *
     */
    private static function wrapLine(string $line, int $maxWidth): string
    {
        preg_match('/^(\s*)/', $line, $m);
        $indent = $m[1];

        $openPos = self::findTopLevelChar($line, '(', strlen($indent));
        if ($openPos === null) {
            return $line;
        }

        $closePos = self::findMatchingParen($line, $openPos);
        if ($closePos === null) {
            return $line;
        }

        $prefix = substr($line, 0, $openPos + 1);
        $body = substr($line, $openPos + 1, $closePos - $openPos - 1);
        $suffix = substr($line, $closePos);

        if (trim($body) === '') {
            return $line;
        }

        $segments = self::splitByTopLevelConcat($body);
        $hasConcat = count($segments) > 1;
        $isLiteral = count($segments) === 1 && self::isStringLiteral($segments[0]);

        if (! $hasConcat && ! $isLiteral) {
            return $line;
        }

        $innerIndent = $indent.'    ';
        $bodyLines = self::wrapExpression($segments, $innerIndent, $maxWidth);

        return $prefix."\n".implode("\n", $bodyLines)."\n".$indent.$suffix;
    }

    /** Обернуть сегменты PHP-выражения с заданным отступом. Возвращает массив строк.
     *
     * @param  array<int, string>  $segments
     * @return array<int, string>
     */
    private static function wrapExpression(array $segments, string $indent, int $maxWidth): array
    {
        $lines = [];
        foreach ($segments as $i => $segment) {
            $prefixConcat = $i === 0 ? '' : '. ';
            $line = $indent.$prefixConcat.$segment;

            if (self::visualLength($line) <= $maxWidth) {
                $lines[] = $line;

                continue;
            }

            if (self::isStringLiteral($segment)) {
                $parts = self::splitStringLiteral($segment, $maxWidth - strlen($indent) - 2);
                foreach ($parts as $j => $part) {
                    $p = $indent.(($i === 0 && $j === 0) ? '' : '. ');
                    $lines[] = $p.$part;
                }

                continue;
            }

            $lines[] = $line;
        }

        return $lines;
    }

    /** Является ли сегмент строковым литералом в одинарных кавычках. */
    private static function isStringLiteral(string $segment): bool
    {
        $len = strlen($segment);

        return $len >= 2 && $segment[0] === "'" && $segment[$len - 1] === "'";
    }

    /** Разбить PHP-литерал в одинарных кавычках на несколько частей.
     * Режем сначала по «реальному» \n, иначе по ближайшему к порогу пробелу.
     * Бережём экранирование addslashes (не режем посреди \X).
     *
     * @param  int  $maxLineWidth  допустимая ширина одной строки без учёта префикса-отступа
     * @return array<int, string>
     */
    private static function splitStringLiteral(string $literal, int $maxLineWidth): array
    {
        $content = substr($literal, 1, -1);
        $maxContent = max(10, $maxLineWidth - 4);

        $parts = [];
        $pos = 0;
        $len = strlen($content);

        while ($len - $pos > $maxContent) {
            $chunk = substr($content, $pos, $maxContent);

            $nl = strrpos($chunk, "\n");
            if ($nl !== false && $nl > 0) {
                $split = $pos + $nl + 1;
            } else {
                $sp = strrpos($chunk, ' ');
                if ($sp !== false && $sp > 0) {
                    $split = $pos + $sp + 1;
                } else {
                    $split = $pos + $maxContent;
                }
            }

            $split = self::adjustForEscape($content, $split, $len);

            if ($split <= $pos) {
                $split = min($pos + $maxContent, $len);
            }

            $parts[] = "'".substr($content, $pos, $split - $pos)."'";
            $pos = $split;
        }

        if ($pos < $len) {
            $parts[] = "'".substr($content, $pos)."'";
        }

        return $parts;
    }

    /** Скорректировать точку реза, чтобы не разорвать экранирование addslashes (\\, \', \0). */
    private static function adjustForEscape(string $content, int $split, int $len): int
    {
        if ($split <= 0 || $split >= $len) {
            return $split;
        }

        $k = $split - 1;
        $slashes = 0;
        while ($k >= 0 && $content[$k] === '\\') {
            $slashes++;
            $k--;
        }

        if ($slashes % 2 === 1) {
            return $split + 1;
        }

        return $split;
    }

    /** Найти первое вхождение символа на верхнем уровне (вне кавычек). Поиск начинается с $startPos. */
    private static function findTopLevelChar(string $line, string $char, int $startPos = 0): ?int
    {
        $inString = null;
        $escape = false;
        $len = strlen($line);

        for ($i = $startPos; $i < $len; $i++) {
            $c = $line[$i];

            if ($escape) {
                $escape = false;

                continue;
            }

            if ($inString !== null) {
                if ($c === '\\') {
                    $escape = true;

                    continue;
                }
                if ($c === $inString) {
                    $inString = null;
                }

                continue;
            }

            if ($c === "'" || $c === '"') {
                $inString = $c;

                continue;
            }

            if ($c === $char) {
                return $i;
            }
        }

        return null;
    }

    /** Найти парную закрывающую ) для открывающей ( в $openPos. */
    private static function findMatchingParen(string $line, int $openPos): ?int
    {
        $inString = null;
        $escape = false;
        $depth = 1;
        $len = strlen($line);

        for ($i = $openPos + 1; $i < $len; $i++) {
            $c = $line[$i];

            if ($escape) {
                $escape = false;

                continue;
            }

            if ($inString !== null) {
                if ($c === '\\') {
                    $escape = true;

                    continue;
                }
                if ($c === $inString) {
                    $inString = null;
                }

                continue;
            }

            if ($c === "'" || $c === '"') {
                $inString = $c;

                continue;
            }

            if ($c === '(') {
                $depth++;
            } elseif ($c === ')') {
                $depth--;
                if ($depth === 0) {
                    return $i;
                }
            }
        }

        return null;
    }

    /** Разбить выражение по оператору « . » на верхнем уровне (вне кавычек и вне скобок).
     *
     * @return array<int, string>
     */
    private static function splitByTopLevelConcat(string $body): array
    {
        $parts = [];
        $current = '';
        $inString = null;
        $escape = false;
        $depth = 0;
        $len = strlen($body);

        for ($i = 0; $i < $len; $i++) {
            $c = $body[$i];

            if ($escape) {
                $current .= $c;
                $escape = false;

                continue;
            }

            if ($inString !== null) {
                $current .= $c;
                if ($c === '\\') {
                    $escape = true;

                    continue;
                }
                if ($c === $inString) {
                    $inString = null;
                }

                continue;
            }

            if ($c === "'" || $c === '"') {
                $current .= $c;
                $inString = $c;

                continue;
            }

            if ($c === '(') {
                $current .= $c;
                $depth++;

                continue;
            }

            if ($c === ')') {
                $current .= $c;
                $depth--;

                continue;
            }

            if ($depth === 0 && $c === ' ' && $i + 2 < $len && $body[$i + 1] === '.' && $body[$i + 2] === ' ') {
                $parts[] = $current;
                $current = '';
                $i += 2;

                continue;
            }

            $current .= $c;
        }

        if ($current !== '') {
            $parts[] = $current;
        }

        return $parts;
    }

    /** Визуальная длина строки: табы считаем как 4 пробела, реальный \n запрещён (считаем длину до него). */
    private static function visualLength(string $line): int
    {
        return mb_strlen($line);
    }
}
