<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_auditoria_revogacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('confirmacao_id')->unique()->constrained('telegram_confirmacoes')->restrictOnDelete();
            $table->string('acao', 40);
            $table->string('antes', 20);
            $table->string('depois', 20);
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_auditoria_revogacoes');
    }
};
