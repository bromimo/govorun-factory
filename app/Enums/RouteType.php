<?php

namespace App\Enums;

enum RouteType: string
{
    case Command = 'command';
    case Phrase = 'phrase';
    case Pattern = 'pattern';
    case Action = 'action';
    case Event = 'event';
    case Media = 'media';
    case Location = 'location';
    case Contact = 'contact';
    case Referral = 'referral';
    case Fallback = 'fallback';
}
