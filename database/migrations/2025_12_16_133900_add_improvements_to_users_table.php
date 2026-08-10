<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | USERS - Dados pessoais, profissionais e segurança
        |--------------------------------------------------------------------------
        */
        Schema::table('users', function (Blueprint $table) {

            // ===== Dados pessoais =====
            if (!Schema::hasColumn('users', 'cpf')) {
                $table->string('cpf')->nullable()->unique()->after('email');
            }

            if (!Schema::hasColumn('users', 'telefone')) {
                $table->string('telefone')->nullable()->after('cpf');
            }

            if (!Schema::hasColumn('users', 'foto')) {
                $table->string('foto')->nullable()->after('telefone');
            }

            if (!Schema::hasColumn('users', 'nivel')) {
                $table->string('nivel')->default('Professor')->after('foto');
            }

            if (!Schema::hasColumn('users', 'ativo')) {
                $table->boolean('ativo')->default(true)->after('nivel')->index();
            }

            if (!Schema::hasColumn('users', 'data_nasc')) {
                $table->date('data_nasc')->nullable()->after('ativo');
            }

            // ===== PIX =====
            if (!Schema::hasColumn('users', 'tipo_chave')) {
                $table->string('tipo_chave')->nullable()->after('data_nasc');
            }

            if (!Schema::hasColumn('users', 'chave_pix')) {
                $table->string('chave_pix')->nullable()->after('tipo_chave');
            }

            // ===== Endereço =====
            if (!Schema::hasColumn('users', 'cep')) {
                $table->string('cep', 10)->nullable();
                $table->string('endereco')->nullable();
                $table->string('numero', 20)->nullable();
                $table->string('complemento')->nullable();
                $table->string('bairro')->nullable();
                $table->string('cidade')->nullable()->index();
                $table->string('estado', 2)->nullable()->index();
            }

            // ===== Profissional =====
            if (!Schema::hasColumn('users', 'formacao')) {
                $table->string('formacao')->nullable();
                $table->text('especializacoes')->nullable();
                $table->string('registro_profissional')->nullable();
            }

            // ===== Contato de emergência =====
            if (!Schema::hasColumn('users', 'contato_emergencia_nome')) {
                $table->string('contato_emergencia_nome')->nullable();
                $table->string('contato_emergencia_telefone')->nullable();
                $table->string('contato_emergencia_parentesco')->nullable();
            }

            // ===== Redes sociais =====
            if (!Schema::hasColumn('users', 'linkedin')) {
                $table->string('linkedin')->nullable();
                $table->string('instagram')->nullable();
            }

            // ===== Segurança =====
            if (!Schema::hasColumn('users', 'ultimo_acesso')) {
                $table->timestamp('ultimo_acesso')->nullable();
                $table->integer('tentativas_login')->default(0);
                $table->timestamp('bloqueado_ate')->nullable();
            }
        });

        /*
        |--------------------------------------------------------------------------
        | PROFESSOR_DOCUMENTOS
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('professor_documentos')) {
            Schema::create('professor_documentos', function (Blueprint $table) {
                $table->id();

                $table->foreignId('user_id')
                    ->constrained()
                    ->cascadeOnDelete();

                // Documento
                $table->string('titulo')->index();
                $table->string('titulo_customizado')->nullable();
                $table->string('caminho');
                $table->text('observacoes')->nullable();

                // Status
                $table->boolean('verificado')->default(false)->index();
                $table->date('data_validade')->nullable()->index();

                // Metadados
                $table->string('tipo_mime')->nullable();
                $table->unsignedBigInteger('tamanho')->nullable();
                $table->string('hash')->nullable()->unique();

                // Controle / auditoria
                $table->unsignedInteger('versao')->default(1);
                $table->foreignId('documento_anterior_id')
                    ->nullable()
                    ->constrained('professor_documentos')
                    ->nullOnDelete();

                $table->foreignId('verificado_por')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp('verificado_em')->nullable();
                $table->text('motivo_rejeicao')->nullable();
                $table->json('tags')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'titulo']);
                $table->index(['user_id', 'verificado']);
                $table->index(['data_validade', 'verificado']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('professor_documentos');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'cpf',
                'telefone',
                'foto',
                'nivel',
                'ativo',
                'data_nasc',
                'tipo_chave',
                'chave_pix',
                'cep',
                'endereco',
                'numero',
                'complemento',
                'bairro',
                'cidade',
                'estado',
                'formacao',
                'especializacoes',
                'registro_profissional',
                'contato_emergencia_nome',
                'contato_emergencia_telefone',
                'contato_emergencia_parentesco',
                'linkedin',
                'instagram',
                'ultimo_acesso',
                'tentativas_login',
                'bloqueado_ate',
            ]);
        });
    }
};
