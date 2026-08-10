<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mensalidade_cobrancas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mensalidade_id')
                  ->constrained('mensalidades')
                  ->cascadeOnDelete();

            // Tipo de cobrança
            $table->string('tipo', 30)
                  ->index()
                  ->comment('lembrete_3dias, cobranca_1dia, cobranca_7dias, cobranca_15dias, cobranca_30dias, manual');

            // Canal de envio
            $table->string('canal', 20)
                  ->comment('whatsapp, email, sms, push, manual');

            // Status da entrega
            $table->string('status', 20)
                  ->default('pendente')
                  ->comment('pendente, enviado, lido, erro, respondido');

            // Conteúdo da mensagem (auditoria)
            $table->text('conteudo')->nullable();
            $table->text('erro_detalhe')->nullable();

            // Metadados (provider response, message_id, etc.)
            $table->json('metadata')->nullable();

            // Quem disparou (null = automático/scheduler)
            $table->foreignId('disparado_por')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamp('enviado_em')->nullable();
            $table->timestamp('lido_em')->nullable();

            $table->timestamps();

            // Indexes para queries comuns
            $table->index(['mensalidade_id', 'created_at']);
            $table->index(['tipo', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensalidade_cobrancas');
    }
};