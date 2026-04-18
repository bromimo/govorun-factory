<?php

namespace App\Observers;

use App\Models\Bot;
use App\Models\BotRoute;
use Illuminate\Support\Facades\Auth;

/** Наблюдатель маршрута бота: при любом изменении маршрута форсированно обновляет updated_at и updated_by у родительского бота. */
class BotRouteObserver
{
    /** Обновить бота при сохранении маршрута.
     */
    public function saved(BotRoute $route): void
    {
        $this->touchBot($route->bot_id);
    }

    /** Обновить бота при удалении маршрута.
     */
    public function deleted(BotRoute $route): void
    {
        $this->touchBot($route->bot_id);
    }

    /** Форсированно обновить updated_at и updated_by у бота через query-билдер, минуя isDirty-проверку.
     */
    private function touchBot(?int $botId): void
    {
        if ($botId === null) {
            return;
        }

        $data = ['updated_at' => now()];
        if (Auth::check()) {
            $data['updated_by'] = Auth::id();
        }

        Bot::where('id', $botId)->update($data);
    }
}
