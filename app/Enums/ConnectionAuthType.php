<?php

namespace App\Enums;

/** Типы авторизации для подключений к внешним API. */
enum ConnectionAuthType: string
{
    case None = 'none';
    case ApiKey = 'api_key';
    case Bearer = 'bearer';
    case Basic = 'basic';
}
