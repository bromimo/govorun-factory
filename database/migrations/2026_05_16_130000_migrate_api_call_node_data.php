<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

/** Миграция данных: api_call-ноды переходят на расширенную схему с connection_id, path, headers и др. */
return new class extends Migration
{
    /** Применить миграцию.
     */
    public function up(): void
    {
        DB::table('bot_flows')->orderBy('id')->lazy()->each(function (object $flow): void {
            $graph = json_decode((string) $flow->graph, true);

            if (! is_array($graph) || empty($graph['nodes'] ?? [])) {
                return;
            }

            $changed = false;

            foreach ($graph['nodes'] as &$node) {
                if (($node['type'] ?? '') !== 'api_call') {
                    continue;
                }

                $dataBefore = $node['data'] ?? [];
                $data = $dataBefore;

                $data['connection_id'] = $data['connection_id'] ?? null;
                $data['method'] = $data['method'] ?? 'GET';
                $data['path'] = $data['path'] ?? '';
                $data['headers'] = $data['headers'] ?? [];
                $data['query'] = $data['query'] ?? [];
                $data['body_mode'] = $data['body_mode'] ?? 'none';
                $data['body'] = $data['body'] ?? null;
                $data['response_mapping'] = $data['response_mapping'] ?? [];
                $data['on_error'] = $data['on_error'] ?? 'stop_flow';
                unset($data['url']);

                if ($data !== $dataBefore) {
                    $node['data'] = $data;
                    $changed = true;
                }
            }
            unset($node);

            if ($changed) {
                DB::table('bot_flows')->where('id', $flow->id)->update([
                    'graph' => json_encode($graph, JSON_UNESCAPED_UNICODE),
                ]);
            }
        });
    }

    /** Откатить миграцию (no-op — данные изменены безвозвратно для упрощения схемы).
     */
    public function down(): void
    {
        //
    }
};
