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
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Может ли пользователь просматривать конкретного бота.
     *
     * @param User $user
     * @param Bot $bot
     * @return bool
     */
    public function view(User $user, Bot $bot): bool
    {
        return true;
    }

    /** Может ли пользователь создавать ботов.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->role !== UserRole::Viewer;
    }

    /** Может ли пользователь обновлять бота.
     *
     * @param User $user
     * @param Bot $bot
     * @return bool
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
     * @param User $user
     * @param Bot $bot
     * @return bool
     */
    public function delete(User $user, Bot $bot): bool
    {
        return $user->role === UserRole::Admin;
    }

    /** Может ли пользователь экспортировать бота.
     *
     * @param User $user
     * @param Bot $bot
     * @return bool
     */
    public function export(User $user, Bot $bot): bool
    {
        return $user->role !== UserRole::Viewer;
    }
}
