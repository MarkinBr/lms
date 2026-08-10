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
        Schema::create('turmas', function (Blueprint $table) {
            $table->id();

            $table->string('nome'); // Ex: "1º Ano Teologia - 2025"
            $table->integer('ano_letivo'); // Ex: 2025
            
            // Turno (Vamos salvar como texto simples: 'Manhã', 'Noite')
            $table->string('turno')->nullable(); 
            
            // Sala (Opcional)
            $table->string('sala')->nullable();

            // Financeiro da Turma
            $table->decimal('valor_total_curso', 10, 2); // Ex: 2400.00
            $table->integer('qtd_parcelas')->default(12); // Ex: 12 parcelas
            
            // Datas
            $table->date('data_inicio')->nullable();
            $table->date('data_fim')->nullable();

            $table->boolean('ativa')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('turmas');
    }
};
