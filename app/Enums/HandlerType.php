<?php

namespace App\Enums;

enum HandlerType: string
{
    case Controller = 'controller';
    case Flow = 'flow';
}
