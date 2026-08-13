<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Issue #32 — Controle de frequência: chamadas (cabeçalho da aula).
     *
     * Compatível com SQLite (testes) e PostgreSQL (produção). O registro é
     * histórico acadêmico: turma/disciplina/criador não podem ser apagados
     * enquanto houver chamada vinculada (restrictOnDelete).
     */
    public function up(): void
    {
        Schema::create('chamadas_frequencia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turma_id')->constrained('turmas')->restrictOnDelete();
            $table->foreignId('disciplina_id')->constrained('disciplinas')->restrictOnDelete();
            $table->date('data_aula');
            $table->string('titulo')->nullable();
            $table->string('status')->default('rascunho');
            $table->foreignId('criada_por')->constrained('users')->restrictOnDelete();
            $table->foreignId('concluida_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['turma_id', 'disciplina_id', 'data_aula'], 'chamadas_frequencia_turma_disciplina_data_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chamadas_frequencia');
    }
};
