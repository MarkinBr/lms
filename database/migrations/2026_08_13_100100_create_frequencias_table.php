<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Issue #32 — Controle de frequência: situação de cada aluno na chamada.
     *
     * Compatível com SQLite (testes) e PostgreSQL (produção). A frequência é
     * histórico acadêmico: a chamada, a matrícula e o usuário registrador não
     * podem ser apagados enquanto houver registro vinculado (restrictOnDelete).
     *
     * Nenhum dado sensível (CPF, e-mail, endereço) é armazenado aqui.
     */
    public function up(): void
    {
        Schema::create('frequencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chamada_frequencia_id')->constrained('chamadas_frequencia')->restrictOnDelete();
            $table->foreignId('matricula_id')->constrained('matriculas')->restrictOnDelete();
            $table->string('situacao');
            $table->text('observacao')->nullable();
            $table->foreignId('registrado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['chamada_frequencia_id', 'matricula_id'], 'frequencias_chamada_matricula_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frequencias');
    }
};
