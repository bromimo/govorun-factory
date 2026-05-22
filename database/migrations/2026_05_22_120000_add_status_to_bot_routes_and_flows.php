<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bot_routes', function (Blueprint $table) {
            $table->string('status', 16)->default('active')->after('middleware');
        });

        Schema::table('bot_flows', function (Blueprint $table) {
            $table->string('status', 16)->default('active')->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('bot_routes', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('bot_flows', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
