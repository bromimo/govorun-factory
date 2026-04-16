<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Применить миграцию. */
    public function up(): void
    {
        Schema::table('bot_routes', function (Blueprint $table) {
            $table->json('aliases')->nullable()->after('match');
        });
    }

    /** Откатить миграцию. */
    public function down(): void
    {
        Schema::table('bot_routes', function (Blueprint $table) {
            $table->dropColumn('aliases');
        });
    }
};
