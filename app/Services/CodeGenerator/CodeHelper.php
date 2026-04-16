<?php

namespace App\Services\CodeGenerator;

/** Вспомогательные методы для генерации PHP-кода. */
class CodeHelper
{
    /** Отрендерить текст с интерполяцией переменных {{var}} → $this->state->get('var').
     * @param  string  $text
     * @return string PHP-выражение
     */
    public static function renderText(string $text): string
    {
        if (! str_contains($text, '{{')) {
            return "'".addslashes($text)."'";
        }

        $parts = preg_split('/(\{\{\w+\}\})/', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $segments = [];
        foreach ($parts as $part) {
            if (preg_match('/^\{\{(\w+)\}\}$/', $part, $m)) {
                $segments[] = "\$this->state->get('{$m[1]}')";
            } else {
                $segments[] = "'".addslashes($part)."'";
            }
        }

        return implode(' . ', $segments);
    }
}
