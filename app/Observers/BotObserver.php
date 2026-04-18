<?php

namespace App\Observers;

use App\Models\Bot;
use Illuminate\Support\Facades\Auth;

/** Наблюдатель бота: проставляет updated_by при создании и изменении. */
class BotObserver
{
    /** Выставить updated_by при создании, если ещё не задан.
     */
    public function creating(Bot $bot): void
    {
        if ($bot->updated_by === null) {
            $bot->updated_by = Auth::id() ?? $bot->created_by;
        }
    }

    /** Выставить updated_by на текущего пользователя при любом изменении.
     */
    public function updating(Bot $bot): void
    {
        if (Auth::check()) {
            $bot->updated_by = Auth::id();
        }
    }
}
