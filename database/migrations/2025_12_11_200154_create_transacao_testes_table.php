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
    Schema::create('transacao_testes', function (Blueprint $table) {
        $table->id();
        $table->string('transacao_id')->unique(); // ID do Mercado Pago
        $table->string('referencia_externa')->nullable();
        $table->string('metodo_pagamento'); // pix ou boleto
        $table->decimal('valor', 10, 2);
        $table->string('status')->default('pending'); // approved, pending, rejected
        $table->json('payload_completo')->nullable(); // Guardar tudo que o MP mandou para debug
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transacao_testes');
    }
};
