<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $textos = ['rg', 'estado_civil', 'nacionalidade', 'naturalidade_cidade', 'naturalidade_estado', 'whatsapp', 'instituicao_anterior', 'como_conheceu', 'indicacao_nome'];
        foreach ($textos as $campo) {
            if (! Schema::hasColumn('users', $campo)) {
                Schema::table('users', fn (Blueprint $t) => $t->string($campo)->nullable());
            }
        }
        if (! Schema::hasColumn('users', 'ano_conclusao')) {
            Schema::table('users', fn (Blueprint $t) => $t->unsignedSmallInteger('ano_conclusao')->nullable());
        }
        if (! Schema::hasColumn('users', 'formacao_teologica')) {
            Schema::table('users', fn (Blueprint $t) => $t->boolean('formacao_teologica')->nullable());
        }
        Schema::create('acesso_auditorias', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ator_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('aluno_id')->constrained('users')->restrictOnDelete();
            $t->string('acao', 80);
            $t->string('motivo', 500);
            $t->text('detalhes')->nullable();
            $t->timestamps();
            $t->index(['aluno_id', 'created_at']);
        });
        Schema::create('acesso_entregas', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('ator_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('aluno_id')->constrained('users')->restrictOnDelete();
            $t->string('canal', 20);
            $t->string('tipo', 40);
            $t->string('estado', 40)->default('preparado');
            $t->unsignedInteger('versao');
            $t->string('destino_hash', 64);
            $t->text('conteudo')->nullable();
            $t->timestamp('expira_em');
            $t->timestamp('visualizado_em')->nullable();
            $t->timestamps();
        });
        Schema::create('acesso_operacoes', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('ator_id')->constrained('users')->restrictOnDelete();
            $t->string('proposta_hash', 64);
            $t->foreignId('aluno_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('expira_em');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        // Dados de atendimento não são apagados automaticamente em retorno de código.
    }
};
