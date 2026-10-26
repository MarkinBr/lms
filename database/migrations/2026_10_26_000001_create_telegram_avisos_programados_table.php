<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_avisos_programados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipo', 12);
            $table->string('periodo', 10);
            $table->string('status', 20);
            $table->timestamp('finalizado_em')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'tipo', 'periodo'], 'tg_aviso_dedupe');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_avisos_programados');
    }
};
