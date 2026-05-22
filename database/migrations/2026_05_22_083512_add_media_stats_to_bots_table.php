<?php

use App\Models\Bot;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

/** Добавить кешированную статистику медиа в bots. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bots', function (Blueprint $table) {
            $table->unsignedInteger('used_media_count')->default(0)->after('messenger_config');
            $table->unsignedBigInteger('used_media_size')->default(0)->after('used_media_count');
        });

        Bot::with([
            'flows:id,bot_id,graph',
            'routes:id,bot_id,handler_schema',
        ])->get(['id'])->each(function (Bot $bot) {
            $ids = $bot->extractUsedMediaIds();
            $size = empty($ids) ? 0 : DB::table('bot_media')->whereIn('id', $ids)->sum('size');
            DB::table('bots')->where('id', $bot->id)->update([
                'used_media_count' => count($ids),
                'used_media_size'  => $size,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('bots', function (Blueprint $table) {
            $table->dropColumn(['used_media_count', 'used_media_size']);
        });
    }
};