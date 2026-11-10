<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secretaria_assinaturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('responsavel_id')->constrained('users')->restrictOnDelete();
            $table->string('cargo', 150);
            $table->string('arquivo_privado');
            $table->string('mime', 40);
            $table->string('origem', 20);
            $table->boolean('ativa')->default(true);
            $table->timestamps();
            $table->index(['responsavel_id', 'ativa']);
        });

        Schema::create('secretaria_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matricula_id')->constrained('matriculas')->restrictOnDelete();
            $table->foreignId('requerimento_id')->nullable()->constrained('requerimentos')->nullOnDelete();
            $table->foreignId('preparado_por')->constrained('users')->restrictOnDelete();
            $table->foreignId('revisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assinatura_id')->nullable()->constrained('secretaria_assinaturas')->nullOnDelete();
            $table->string('tipo', 45);
            $table->string('modelo', 20)->default('classico');
            $table->string('status', 25)->default('rascunho');
            $table->string('finalidade', 500);
            $table->json('dados_congelados')->nullable();
            $table->text('observacao_revisao')->nullable();
            $table->timestamp('enviado_em')->nullable();
            $table->timestamp('revisado_em')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('secretaria_documento_movimentacoes', function (Blueprint $table) {
            $table->id();
            $table->string('entidade', 35);
            $table->string('entidade_id', 60);
            $table->foreignId('ator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('acao', 40);
            $table->string('estado_anterior', 40)->nullable();
            $table->string('estado_novo', 40)->nullable();
            $table->text('motivo')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['entidade', 'entidade_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        // A trilha de auditoria é intencionalmente preservada durante rollback,
        // evitando exclusão silenciosa de movimentações acadêmicas.
        // Não descartar estes registros sem migração de arquivamento aprovada.
    }
};
