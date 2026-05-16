<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug', 64);
            $table->string('base_url', 2048);
            $table->string('auth_type', 32)->default('none');
            $table->text('auth_config')->nullable();
            $table->json('default_headers')->nullable();
            $table->timestamps();

            $table->unique(['bot_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_connections');
    }
};
