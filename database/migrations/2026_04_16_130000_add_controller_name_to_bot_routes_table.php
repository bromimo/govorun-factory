<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** Применить миграцию. */
    public function up(): void
    {
        Schema::table('bot_routes', function (Blueprint $table) {
            $table->string('controller_name')->nullable()->after('aliases');
        });
    }

    /** Откатить миграцию. */
    public function down(): void
    {
        Schema::table('bot_routes', function (Blueprint $table) {
            $table->dropColumn('controller_name');
        });
    }
};
