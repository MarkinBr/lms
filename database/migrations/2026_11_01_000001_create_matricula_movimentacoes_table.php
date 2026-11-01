<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matricula_movimentacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matricula_id')->constrained('matriculas')->restrictOnDelete();
            $table->foreignId('turma_id')->nullable()->constrained('turmas')->restrictOnDelete();
            $table->foreignId('ator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo', 40);
            $table->string('valor_anterior', 100)->nullable();
            $table->string('valor_novo', 100)->nullable();
            $table->string('motivo', 500);
            $table->timestamps();
            $table->index(['matricula_id', 'created_at']);
        });
    }

    public function down(): void
    {
        // Histórico acadêmico: nunca apagar automaticamente em rollback.
    }
};
