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
        Schema::create('despesas', function (Blueprint $table) {
            $table->id();
            
            // Campos Básicos
            $table->string('descricao'); 
            $table->foreignId('categoria_id')->constrained('categoria_financeiras');
            $table->decimal('valor', 10, 2);
            $table->date('data_vencimento');
            $table->date('data_pagamento')->nullable();
            $table->string('fornecedor')->nullable();
            $table->string('status')->default('Pendente');

            // Campos Avançados (Comprovante e Conta) - Já incluídos aqui!
            $table->string('comprovante')->nullable();
            $table->foreignId('conta_id')->nullable()->constrained('contas');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('despesas');
    }
};
