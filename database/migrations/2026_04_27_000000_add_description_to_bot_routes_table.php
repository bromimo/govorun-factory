<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** Добавить поле description к маршрутам — для подсказки в Telegram-меню команд.
     */
    public function up(): void
    {
        Schema::table('bot_routes', function (Blueprint $table) {
            $table->string('description', 256)->nullable()->after('match');
        });
    }

    /** Откатить миграцию.
     */
    public function down(): void
    {
        Schema::table('bot_routes', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
