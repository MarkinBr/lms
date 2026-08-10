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
        Schema::create('alunos', function (Blueprint $table) {
            $table->id();

            // Dados Pessoais
            $table->string('nome');
            $table->string('email')->nullable();
            $table->string('cpf')->nullable();
            $table->string('telefone')->nullable();
            $table->date('data_nascimento'); // Essencial para calcular se é menor de 18
            
            // Endereço
            $table->string('endereco')->nullable();
            $table->string('bairro')->nullable();
            $table->string('cidade')->nullable();
            $table->string('estado')->nullable();
            $table->string('cep')->nullable();
            
            // Dados do Responsável (Nullable = opcional, só vamos obrigar no painel se for menor)
            $table->string('nome_responsavel')->nullable();
            $table->string('telefone_responsavel')->nullable();
            $table->string('cpf_responsavel')->nullable();

            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alunos');
    }
};
