<?php

namespace App\Policies;

use App\Models\Bot;
use App\Models\User;
use App\Enums\UserRole;

/** Политика доступа к ботам. */
class BotPolicy
{
    /** Может ли пользователь просматривать список ботов.
     *
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Может ли пользователь просматривать конкретного бота.
     *
     */
    public function view(User $user, Bot $bot): bool
    {
        return true;
    }

    /** Может ли пользователь создавать ботов.
     *
     */
    public function create(User $user): bool
    {
        return $user->role !== UserRole::Viewer;
    }

    /** Может ли пользователь обновлять бота.
     *
     */
    public function update(User $user, Bot $bot): bool
    {
        return match ($user->role) {
            UserRole::Admin => true,
            UserRole::Editor => $user->id === $bot->created_by,
            default => false,
        };
    }

    /** Может ли пользователь удалять бота.
     *
     */
    public function delete(User $user, Bot $bot): bool
    {
        return $user->role === UserRole::Admin;
    }

    /** Может ли пользователь экспортировать бота.
     *
     */
    public function export(User $user, Bot $bot): bool
    {
        return $user->role !== UserRole::Viewer;
    }
}
