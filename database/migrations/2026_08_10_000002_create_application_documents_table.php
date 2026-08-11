<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tabela de documentos das inscrições
     * Armazena cada documento enviado pelo candidato separadamente
     */
    public function up(): void
    {
        Schema::create('application_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('application_id');

            // Tipo de documento
            $table->enum('tipo', [
                'foto_3x4',
                'rg',
                'cpf',
                'comprovante_residencia',
                'historico_escolar',
                'certificado_conclusao',
                'outro',
            ]);

            // Arquivo
            $table->string('nome_original');
            $table->string('nome_armazenado'); // Nome seguro no storage
            $table->string('mime_type');
            $table->unsignedBigInteger('tamanho'); // em bytes
            $table->string('caminho_arquivo'); // path relativo no storage
            $table->string('url_publica')->nullable(); // URL temporária se aplicável

            // Validação
            $table->boolean('validado')->default(false);
            $table->text('observacoes_validacao')->nullable();
            $table->foreignId('validado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validado_em')->nullable();

            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();

            // Índices
            $table->index('application_id');
            $table->index('tipo');
            $table->index('validado');

            // Foreign keys
            $table->foreign('application_id')
                ->references('id')
                ->on('applications')
                ->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_documents');
    }
};
