<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Применить миграцию.
     * @return void
     */
    public function up(): void
    {
        Schema::table('bot_flows', function (Blueprint $table) {
            $table->string('description')->nullable()->after('name');
        });
    }

    /** Откатить миграцию.
     * @return void
     */
    public function down(): void
    {
        Schema::table('bot_flows', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
