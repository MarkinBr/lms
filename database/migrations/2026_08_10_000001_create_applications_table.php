<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabela de inscrições de candidatos ao CETSADP
     * Fluxo: Candidato se inscreve → Secretaria analisa → Aprova/Rejeita → Matrícula
     */
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Dados pessoais do candidato
            $table->string('nome_completo');
            $table->string('cpf')->unique();
            $table->string('rg')->nullable();
            $table->date('data_nascimento');
            $table->string('sexo'); // M, F, Outro
            $table->string('estado_civil')->nullable();
            $table->string('nacionalidade')->default('Brasileira');
            $table->string('naturalidade_cidade')->nullable();
            $table->string('naturalidade_estado')->nullable();
            
            // Contato
            $table->string('email');
            $table->string('telefone');
            $table->string('whatsapp')->nullable();
            
            // Endereço
            $table->string('cep');
            $table->string('logradouro');
            $table->string('numero');
            $table->string('complemento')->nullable();
            $table->string('bairro');
            $table->string('cidade');
            $table->string('estado');
            
            // Dados acadêmicos
            $table->string('escolaridade');
            $table->uuid('curso_id');
            $table->uuid('course_modality_id');
            
            // Status da inscrição
            $table->enum('status', [
                'em_analise',      // Aguardando análise da secretaria
                'aprovado',        // Aprovado, aguardando matrícula
                'rejeitado',       // Rejeitado pela secretaria
                'matriculado',     // Já foi matriculado
                'cancelado'        // Candidato cancelou
            ])->default('em_analise');
            
            // Documentos
            $table->json('documentos_urls')->nullable(); // URLs dos arquivos uploadados
            
            // Análise da secretaria
            $table->text('observacoes_secretaria')->nullable();
            $table->uuid('analisado_por_id')->nullable();
            $table->timestamp('analisado_em')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable(); // Soft delete
            
            // Índices
            $table->index('cpf');
            $table->index('email');
            $table->index('status');
            $table->index('curso_id');
            $table->index('course_modality_id');
            $table->index('analisado_por_id');
            
            // Foreign keys
            $table->foreign('curso_id')
                  ->references('id')
                  ->on('cursos')
                  ->onDelete('restrict');
                  
            $table->foreign('course_modality_id')
                  ->references('id')
                  ->on('course_modalities')
                  ->onDelete('restrict');
                  
            $table->foreign('analisado_por_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
