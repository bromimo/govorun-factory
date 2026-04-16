<?php

namespace App\Services\CodeGenerator;

/** Вспомогательные методы для генерации PHP-кода. */
class CodeHelper
{
    /** Отрендерить текст с интерполяцией переменных.
     * {{var}} → $this->state->get('var')
     * {{user.firstName}} → $this->message->user->firstName
     *
     * @param  string  $text
     * @return string PHP-выражение
     */
    public static function renderText(string $text): string
    {
        if (! str_contains($text, '{{')) {
            return "'".addslashes($text)."'";
        }

        $parts = preg_split('/(\{\{[\w.]+\}\})/', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $segments = [];
        foreach ($parts as $part) {
            if (preg_match('/^\{\{([\w.]+)\}\}$/', $part, $m)) {
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
     * @param  string  $variable
     * @return string PHP-выражение
     */
    private static function renderVariable(string $variable): string
    {
        if (str_contains($variable, '.')) {
            return '$this->message->'.str_replace('.', '->', $variable);
        }

        return "\$this->state->get('{$variable}')";
    }
}
