<?php

use App\Services\TelegramHtml;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Миграция данных: экранировать plain-text в полях text узлов ask/reply (text_format_version 1 → 2). */
return new class extends Migration
{
    /** Применить миграцию.
     * @return void
     */
    public function up(): void
    {
        $botIds = DB::table('bots')->where('text_format_version', 1)->pluck('id');

        if ($botIds->isEmpty()) {
            return;
        }

        $this->migrateFlows($botIds->all());
        $this->migrateRoutes($botIds->all());

        DB::table('bots')->whereIn('id', $botIds->all())->update(['text_format_version' => 2]);
    }

    /** Откатить миграцию (no-op).
     * @return void
     */
    public function down(): void {}

    /** Экранировать текст в нодах flow.
     * @param array<int, int> $botIds
     * @return void
     */
    private function migrateFlows(array $botIds): void
    {
        DB::table('bot_flows')->whereIn('bot_id', $botIds)->orderBy('id')->lazy()
            ->each(function (object $flow): void {
                $graph = json_decode((string) $flow->graph, true);
                if (! is_array($graph)) {
                    return;
                }

                $changed = false;
                $nodes = $graph['nodes'] ?? [];
                foreach (array_keys($nodes) as $i) {
                    $type = $graph['nodes'][$i]['type'] ?? '';
                    if (! in_array($type, ['ask', 'reply'], true)) {
                        continue;
                    }
                    $text = $graph['nodes'][$i]['data']['text'] ?? null;
                    if ($text === null || $text === '') {
                        continue;
                    }
                    $escaped = TelegramHtml::htmlEscapeKeepPlaceholders((string) $text);
                    if ($escaped !== $text) {
                        $graph['nodes'][$i]['data']['text'] = $escaped;
                        $changed = true;
                    }
                }

                if ($changed) {
                    DB::table('bot_flows')->where('id', $flow->id)->update([
                        'graph' => json_encode($graph, JSON_UNESCAPED_UNICODE),
                    ]);
                }
            });
    }

    /** Экранировать текст в блоках route handler_schema.
     * @param array<int, int> $botIds
     * @return void
     */
    private function migrateRoutes(array $botIds): void
    {
        DB::table('bot_routes')->whereIn('bot_id', $botIds)->orderBy('id')->lazy()
            ->each(function (object $route): void {
                $schema = json_decode((string) $route->handler_schema, true);
                if (! is_array($schema)) {
                    return;
                }

                $changed = false;
                $blocks = $schema['blocks'] ?? [];
                foreach (array_keys($blocks) as $i) {
                    if (($schema['blocks'][$i]['type'] ?? '') !== 'reply') {
                        continue;
                    }
                    $text = $schema['blocks'][$i]['params']['text'] ?? null;
                    if ($text === null || $text === '') {
                        continue;
                    }
                    $escaped = TelegramHtml::htmlEscapeKeepPlaceholders((string) $text);
                    if ($escaped !== $text) {
                        $schema['blocks'][$i]['params']['text'] = $escaped;
                        $changed = true;
                    }
                }

                if ($changed) {
                    DB::table('bot_routes')->where('id', $route->id)->update([
                        'handler_schema' => json_encode($schema, JSON_UNESCAPED_UNICODE),
                    ]);
                }
            });
    }
};