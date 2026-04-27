<?php

namespace App\Observers;

use App\Models\Bot;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/** Наблюдатель бота: проставляет updated_by и чистит файлы при удалении. */
class BotObserver
{
    /** Выставить updated_by при создании, если ещё не задан.
     * @return void
     */
    public function creating(Bot $bot): void
    {
        if ($bot->updated_by === null) {
            $bot->updated_by = Auth::id() ?? $bot->created_by;
        }
    }

    /** Выставить updated_by на текущего пользователя при любом изменении.
     * @return void
     */
    public function updating(Bot $bot): void
    {
        if (Auth::check()) {
            $bot->updated_by = Auth::id();
        }
    }

    /** Удалить каталог с аватаром бота при удалении модели.
     * @return void
     */
    public function deleted(Bot $bot): void
    {
        Storage::disk('local')->deleteDirectory("bot-profiles/{$bot->id}");
    }
}
