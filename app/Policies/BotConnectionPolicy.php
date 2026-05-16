<?php

namespace App\Policies;

use App\Models\User;
use App\Enums\UserRole;
use App\Models\BotConnection;

/** Политика доступа к подключениям бота. */
class BotConnectionPolicy
{
    /** Просмотр подключения. */
    public function view(User $user, BotConnection $connection): bool
    {
        return true;
    }

    /** Изменение подключения. */
    public function update(User $user, BotConnection $connection): bool
    {
        return match ($user->role) {
            UserRole::Admin => true,
            UserRole::Editor => $user->id === $connection->bot->created_by,
            default => false,
        };
    }

    /** Удаление подключения. */
    public function delete(User $user, BotConnection $connection): bool
    {
        return $this->update($user, $connection);
    }
}
