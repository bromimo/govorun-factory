<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** Обнулить controller_name у всех fallback-маршрутов: для fallback оно игнорируется
     * генератором (контроллер всегда называется FallbackController) и больше не запрашивается в UI.
     */
    public function up(): void
    {
        DB::table('bot_routes')
            ->where('type', 'fallback')
            ->whereNotNull('controller_name')
            ->update(['controller_name' => null]);
    }

    /** Откат необратим: данные не восстанавливаются. */
    public function down(): void
    {
        //
    }
};
