<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('matriculas', function (Blueprint $table) {
            $table->id();

            // Aqui está o segredo: Chaves Estrangeiras (Ligação com as outras tabelas)
            // O 'constrained' impede que você matricule um aluno que não existe
            $table->foreignId('aluno_id')->constrained('alunos')->onDelete('cascade');
            $table->foreignId('turma_id')->constrained('turmas')->onDelete('cascade');
            
            $table->date('data_matricula')->default(now());

            $table->integer('dia_vencimento')->default(10); // Ex: 5, 10, 15
            
            // Status da matrícula (Ativa, Trancada, Cancelada, Concluída)
            $table->string('status')->default('Ativa'); 
            
            // Número de matrícula (opcional, pode ser gerado automático)
            $table->string('numero_matricula')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matriculas');
    }
};
