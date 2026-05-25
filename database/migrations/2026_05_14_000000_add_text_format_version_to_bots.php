<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/** Добавить столбец text_format_version в таблицу bots. */
return new class extends Migration
{
    /** Применить миграцию.
     */
    public function up(): void
    {
        Schema::table('bots', function (Blueprint $table): void {
            $table->integer('text_format_version')->default(1)->after('config');
        });
    }

    /** Откатить миграцию.
     */
    public function down(): void
    {
        Schema::table('bots', function (Blueprint $table): void {
            $table->dropColumn('text_format_version');
        });
    }
};
