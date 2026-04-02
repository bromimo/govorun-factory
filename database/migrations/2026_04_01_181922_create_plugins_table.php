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
        Schema::create('plugins', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->json('block_schema')->nullable();
            $table->string('vue_component')->nullable();
            $table->text('php_stub')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /** Откатить миграцию.
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('plugins');
    }
};
