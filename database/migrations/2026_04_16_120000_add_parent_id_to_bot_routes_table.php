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
            $table->foreignId('parent_id')->nullable()->after('bot_id')->constrained('bot_routes')->cascadeOnDelete();
        });
    }

    /** Откатить миграцию. */
    public function down(): void
    {
        Schema::table('bot_routes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
