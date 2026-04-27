<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Добавить поле description к маршрутам — для подсказки в Telegram-меню команд.
     * @return void
     */
    public function up(): void
    {
        Schema::table('bot_routes', function (Blueprint $table) {
            $table->string('description', 256)->nullable()->after('match');
        });
    }

    /** Откатить миграцию.
     * @return void
     */
    public function down(): void
    {
        Schema::table('bot_routes', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
