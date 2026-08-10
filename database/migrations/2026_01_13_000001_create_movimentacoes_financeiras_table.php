<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Só cria se a tabela NÃO existir
        if (!Schema::hasTable('movimentacoes_financeiras')) {
            Schema::create('movimentacoes_financeiras', function (Blueprint $table) {
                $table->id();
                
                // Tipo e dados básicos
                $table->enum('tipo', ['entrada', 'saida']);
                $table->foreignId('conta_id')->constrained('contas')->cascadeOnDelete();
                $table->foreignId('categoria_id')->nullable()->constrained('categoria_financeiras')->nullOnDelete();
                $table->foreignId('mensalidade_id')->nullable()->constrained('mensalidades')->nullOnDelete();
                
                // Valores e descrição
                $table->string('descricao');
                $table->decimal('valor', 10, 2);
                $table->date('data_movimentacao');
                
                // Status
                $table->enum('status', ['efetivado', 'pendente', 'cancelado'])->default('efetivado');
                
                // Partes envolvidas
                $table->string('beneficiario')->nullable();
                $table->string('pagador')->nullable();
                
                // Documentação
                $table->string('comprovante')->nullable();
                $table->text('observacoes')->nullable();
                
                // Auditoria
                $table->foreignId('criado_por')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('alterado_por')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('data_alteracao')->nullable();
                
                $table->timestamps();
                $table->softDeletes();
                
                $table->index(['tipo', 'data_movimentacao']);
                $table->index(['status', 'data_movimentacao']);
                $table->index('conta_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('movimentacoes_financeiras');
    }
};