<?php

namespace App\Observers;

use App\Models\Bot;
use App\Models\BotFlow;
use App\Models\BotMedia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** Наблюдатель flow-диалога: при любом изменении flow форсированно обновляет updated_at и updated_by у родительского бота. */
class BotFlowObserver
{
    /** Обновить бота и пересчитать статистику нод при сохранении flow.
     */
    public function saved(BotFlow $flow): void
    {
        $this->recomputeStats($flow);
        Bot::recomputeMediaStats($flow->bot_id);
        $this->touchBot($flow->bot_id);
    }

    /** Обновить бота при удалении flow.
     */
    public function deleted(BotFlow $flow): void
    {
        Bot::recomputeMediaStats($flow->bot_id);
        $this->touchBot($flow->bot_id);
    }

    /** Пересчитать и сохранить кешированную статистику нод flow.
     * Использует query-билдер, чтобы не вызвать повторный saved().
     */
    private function recomputeStats(BotFlow $flow): void
    {
        $nodes = $flow->graph['nodes'] ?? [];

        $mediaIds = array_unique(array_filter(array_map(
            fn ($n) => isset($n['data']['media']['media_id']) ? (int) $n['data']['media']['media_id'] : null,
            $nodes,
        )));

        $usedMediaSize = empty($mediaIds)
            ? 0
            : BotMedia::whereIn('id', $mediaIds)->sum('size');

        DB::table('bot_flows')->where('id', $flow->id)->update([
            'blocks_count'     => count($nodes),
            'ask_count'        => count(array_filter($nodes, fn ($n) => ($n['type'] ?? '') === 'ask')),
            'api_call_count'   => count(array_filter($nodes, fn ($n) => ($n['type'] ?? '') === 'api_call')),
            'used_media_count' => count($mediaIds),
            'used_media_size'  => $usedMediaSize,
        ]);
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
