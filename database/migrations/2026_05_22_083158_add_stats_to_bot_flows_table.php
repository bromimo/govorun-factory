<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

/** Добавить кешированные счётчики нод в bot_flows. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_flows', function (Blueprint $table) {
            $table->unsignedInteger('blocks_count')->default(0)->after('graph');
            $table->unsignedInteger('ask_count')->default(0)->after('blocks_count');
            $table->unsignedInteger('api_call_count')->default(0)->after('ask_count');
            $table->unsignedInteger('used_media_count')->default(0)->after('api_call_count');
            $table->unsignedBigInteger('used_media_size')->default(0)->after('used_media_count');
        });

        DB::table('bot_flows')->get(['id', 'graph'])->each(function (object $row) {
            $nodes = json_decode($row->graph, true)['nodes'] ?? [];

            $mediaIds = array_unique(array_filter(array_map(
                fn ($n) => isset($n['data']['media']['media_id']) ? (int) $n['data']['media']['media_id'] : null,
                $nodes,
            )));

            $usedMediaSize = empty($mediaIds)
                ? 0
                : DB::table('bot_media')->whereIn('id', $mediaIds)->sum('size');

            DB::table('bot_flows')->where('id', $row->id)->update([
                'blocks_count'     => count($nodes),
                'ask_count'        => count(array_filter($nodes, fn ($n) => ($n['type'] ?? '') === 'ask')),
                'api_call_count'   => count(array_filter($nodes, fn ($n) => ($n['type'] ?? '') === 'api_call')),
                'used_media_count' => count($mediaIds),
                'used_media_size'  => $usedMediaSize,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('bot_flows', function (Blueprint $table) {
            $table->dropColumn(['blocks_count', 'ask_count', 'api_call_count', 'used_media_count', 'used_media_size']);
        });
    }
};