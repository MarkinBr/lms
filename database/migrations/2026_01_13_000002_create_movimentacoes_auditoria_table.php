<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Só cria se a tabela NÃO existir
        if (!Schema::hasTable('movimentacoes_auditoria')) {
            Schema::create('movimentacoes_auditoria', function (Blueprint $table) {
                $table->id();
                
                $table->foreignId('movimentacao_id')->constrained('movimentacoes_financeiras')->cascadeOnDelete();
                $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
                
                $table->enum('acao', ['criacao', 'edicao', 'cancelamento', 'restauracao']);
                
                $table->json('dados_anteriores')->nullable();
                $table->json('dados_novos')->nullable();
                
                $table->text('motivo')->nullable();
                
                $table->timestamps();
                
                $table->index(['movimentacao_id', 'created_at']);
                $table->index('usuario_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('movimentacoes_auditoria');
    }
};