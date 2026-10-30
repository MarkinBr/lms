<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->unsignedInteger('acesso_versao')->default(0);
            $t->boolean('senha_definitiva_pendente')->default(false);
        });
        Schema::create('acesso_credenciais', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('ator_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('aluno_id')->constrained('users')->restrictOnDelete();
            $t->string('tipo', 20);
            $t->string('canal', 20);
            $t->string('segredo_hash');
            $t->string('destino_hash', 64);
            $t->unsignedInteger('acesso_versao');
            $t->unsignedSmallInteger('tentativas')->default(0);
            $t->timestamp('expira_em');
            $t->timestamp('consumida_em')->nullable();
            $t->timestamp('revogada_em')->nullable();
            $t->timestamp('concluida_em')->nullable();
            $t->timestamps();
            $t->index(['aluno_id', 'created_at']);
        });
    }

    public function down(): void
    {
        // Preservar versões e histórico de segurança em retorno de código.
    }
};
