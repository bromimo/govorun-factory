<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

/** Пересчитать ask_count: тип ноды 'ask', а не 'ask_text'/'ask_keyboard'. */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('bot_flows')->get(['id', 'graph'])->each(function (object $row) {
            $nodes = json_decode($row->graph, true)['nodes'] ?? [];
            $askCount = count(array_filter($nodes, fn ($n) => ($n['type'] ?? '') === 'ask'));
            DB::table('bot_flows')->where('id', $row->id)->update(['ask_count' => $askCount]);
        });
    }

    public function down(): void {}
};
