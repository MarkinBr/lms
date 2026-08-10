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
        Schema::create('mensalidades', function (Blueprint $table) {
            $table->id();

            // Liga a cobrança à matrícula (assim sabemos quem é o aluno e a turma de uma vez só)
            $table->foreignId('matricula_id')->constrained('matriculas')->onDelete('cascade');
            
            $table->decimal('valor', 10, 2);
            $table->date('data_vencimento');

            // --- CAMPOS PARA MERCADO PAGO (Pix e Boleto) ---
            $table->string('transacao_id')->nullable(); // ID do MP
            $table->string('metodo_pagamento')->nullable(); // 'pix', 'boleto', 'cartao'
            
            $table->text('link_pagamento')->nullable(); // Link do Checkout (Geral)
            $table->text('pix_copia_cola')->nullable(); // Código Pix
            $table->text('link_boleto')->nullable();    // Link direto do PDF do Boleto
            $table->string('linha_digitavel')->nullable(); // <--- NOVO: O número do código de barras
            // -----------------------------------------------
            
            // Status do pagamento
            $table->string('status')->default('Pendente'); // Pendente, Pago, Atrasado
            $table->date('data_pagamento')->nullable(); // Só preenche quando pagar
            
            $table->string('observacao')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mensalidades');
    }
};
