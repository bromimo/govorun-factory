<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Добавить столбец text_format_version в таблицу bots. */
return new class extends Migration
{
    /** Применить миграцию.
     * @return void
     */
    public function up(): void
    {
        Schema::table('bots', function (Blueprint $table): void {
            $table->integer('text_format_version')->default(1)->after('config');
        });
    }

    /** Откатить миграцию.
     * @return void
     */
    public function down(): void
    {
        Schema::table('bots', function (Blueprint $table): void {
            $table->dropColumn('text_format_version');
        });
    }
};