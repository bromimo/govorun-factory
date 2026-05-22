<?php

namespace App\Enums;

/** Статус сущности (маршрута или диалога). */
enum EntityStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Draft = 'draft';
}