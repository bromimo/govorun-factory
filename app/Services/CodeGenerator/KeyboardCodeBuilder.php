<?php

namespace App\Services\CodeGenerator;

/** Сериализатор inline/reply-клавиатуры в PHP-код Govorun\Messaging\Keyboard. */
class KeyboardCodeBuilder
{
    /** Отрендерить клавиатуру из полного объекта keyboard (с type, resize, oneTime, buttons).
     * @param  array<string, mixed>  $keyboard  Объект клавиатуры из схемы бота.
     * @param  string  $pad  Базовый отступ для открывающей строки и закрывающей скобки.
     */
    public static function renderKeyboard(array $keyboard, string $pad): string
    {
        $type = $keyboard['type'] ?? 'inline';
        $rows = $keyboard['buttons'] ?? [];

        $factory = $type === 'reply' ? 'Keyboard::reply()' : 'Keyboard::make()';
        $rowsCode = self::renderRows($rows, $pad);

        $code = "{$factory}->buttons([\n{$rowsCode}\n{$pad}])";

        if ($type === 'reply') {
            if (! empty($keyboard['resize'])) {
                $code .= "\n{$pad}    ->resize()";
            }
            if (! empty($keyboard['oneTime'])) {
                $code .= "\n{$pad}    ->oneTime()";
            }
        }

        return $code;
    }

    /** Отрендерить тело Keyboard::make()->buttons([…]).
     * @param  array<int, array<int, array<string, mixed>>>  $rows  Ряды кнопок.
     * @param  string  $pad  Базовый отступ для открывающей строки и закрывающей скобки.
     */
    public static function render(array $rows, string $pad): string
    {
        $rowsCode = self::renderRows($rows, $pad);

        return "Keyboard::make()->buttons([\n{$rowsCode}\n{$pad}])";
    }

    /** Отрендерить ряды кнопок в строку PHP-кода.
     * @param  array<int, array<int, array<string, mixed>>>  $rows
     */
    private static function renderRows(array $rows, string $pad): string
    {
        $rowIndent = $pad.'    ';
        $btnIndent = $pad.'        ';

        $rowsRendered = array_map(function (array $row) use ($rowIndent, $btnIndent) {
            if (empty($row)) {
                return "{$rowIndent}[],";
            }

            $buttons = array_map(
                fn (array $btn) => $btnIndent.CodeHelper::renderButton($btn).',',
                $row,
            );

            return "{$rowIndent}[\n".implode("\n", $buttons)."\n{$rowIndent}],";
        }, $rows);

        return implode("\n", $rowsRendered);
    }
}
