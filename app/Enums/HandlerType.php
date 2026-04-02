<?php

namespace App\Enums;

/** Типы обработчиков маршрута. */
enum HandlerType: string
{
    case Controller = 'controller';
    case Flow = 'flow';
}
