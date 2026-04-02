<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Bot;
use App\Models\User;

class BotPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Bot $bot): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role !== UserRole::Viewer;
    }

    public function update(User $user, Bot $bot): bool
    {
        return match ($user->role) {
            UserRole::Admin => true,
            UserRole::Editor => $user->id === $bot->created_by,
            default => false,
        };
    }

    public function delete(User $user, Bot $bot): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function export(User $user, Bot $bot): bool
    {
        return $user->role !== UserRole::Viewer;
    }
}
