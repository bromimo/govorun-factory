<?php

namespace App\Observers;

use App\Models\Bot;
use App\Models\BotFlow;
use Illuminate\Support\Facades\Auth;

/** Наблюдатель flow-диалога: при любом изменении flow форсированно обновляет updated_at и updated_by у родительского бота. */
class BotFlowObserver
{
    /** Обновить бота при сохранении flow.
     */
    public function saved(BotFlow $flow): void
    {
        $this->touchBot($flow->bot_id);
    }

    /** Обновить бота при удалении flow.
     */
    public function deleted(BotFlow $flow): void
    {
        $this->touchBot($flow->bot_id);
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
