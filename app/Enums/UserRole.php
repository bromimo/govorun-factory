<?php

namespace App\Enums;

/** Роли пользователей системы. */
enum UserRole: string
{
    case Admin = 'admin';
    case Editor = 'editor';
    case Viewer = 'viewer';
}
