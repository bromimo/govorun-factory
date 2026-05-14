<?php

namespace App\Services\CodeGenerator;

/** Сериализатор inline/reply-клавиатуры в PHP-код Govorun\Messaging\Keyboard. */
class KeyboardCodeBuilder
{
    /** Отрендерить тело Keyboard::make()->buttons([…]).
     * @param array<int, array<int, array<string, mixed>>> $rows Ряды кнопок.
     * @param string $pad Базовый отступ для открывающей строки и закрывающей скобки.
     * @return string
     */
    public static function render(array $rows, string $pad): string
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

        $rowsCode = implode("\n", $rowsRendered);

        return "Keyboard::make()->buttons([\n{$rowsCode}\n{$pad}])";
    }
}