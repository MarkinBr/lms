<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financeiro_envios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mensalidade_id')->constrained('mensalidades')->restrictOnDelete();
            $table->foreignId('criado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('canal', 20);
            $table->string('modelo', 20);
            $table->string('status', 24)->default('agendado');
            $table->string('destinatario', 255);
            $table->timestamp('agendado_para');
            $table->timestamp('enviado_em')->nullable();
            $table->text('erro')->nullable();
            $table->timestamps();
            $table->index(['status', 'agendado_para']);
        });
    }

    public function down(): void
    {
        // Mensagens enviadas e tentativas de comunicação têm valor de auditoria.
        // Descarte apenas após política de retenção e migração de arquivamento.
    }
};
