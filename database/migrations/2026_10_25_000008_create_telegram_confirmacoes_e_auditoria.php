<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_confirmacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('chat_id');
            $table->string('codigo_hash', 64)->unique();
            $table->string('acao', 40);
            $table->string('status', 20)->default('pendente')->index();
            $table->timestamp('expira_em');
            $table->timestamp('consumido_em')->nullable();
            $table->timestamps();
        });

        Schema::create('telegram_auditoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('confirmacao_id')->constrained('telegram_confirmacoes')->cascadeOnDelete();
            $table->unsignedBigInteger('update_id')->unique();
            $table->string('acao', 40);
            $table->string('antes', 20);
            $table->string('depois', 20);
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_auditoria');
        Schema::dropIfExists('telegram_confirmacoes');
    }
};
