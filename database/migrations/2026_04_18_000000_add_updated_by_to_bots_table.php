<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Применить миграцию: добавить колонку updated_by и проставить её значением created_by у существующих ботов.
     */
    public function up(): void
    {
        Schema::table('bots', function (Blueprint $table) {
            $table->foreignId('updated_by')->nullable()->after('created_by')
                ->constrained('users')->nullOnDelete();
        });

        DB::table('bots')->update(['updated_by' => DB::raw('created_by')]);
    }

    /** Откатить миграцию.
     */
    public function down(): void
    {
        Schema::table('bots', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
        });
    }
};
