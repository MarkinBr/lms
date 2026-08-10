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
        // O nome da tabela AQUI tem que ser igual ao que está no constrained()
        Schema::create('categoria_financeiras', function (Blueprint $table) {
            $table->id();
            $table->string('nome'); 
            $table->string('tipo')->default('Despesa'); // Receita ou Despesa
            $table->boolean('ativa')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categoria_financeiras');
    }
};
