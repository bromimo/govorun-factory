<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** Применить миграцию.
     */
    public function up(): void
    {
        Schema::create('bot_flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained('bots')->cascadeOnDelete();
            $table->string('name');
            $table->json('graph')->nullable();
            $table->json('interrupt_commands')->nullable();
            $table->boolean('interrupt_on_event')->default(false);
            $table->timestamps();
        });
    }

    /** Откатить миграцию.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_flows');
    }
};
