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
        | USERS - Complemento
        |--------------------------------------------------------------------------
        */
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'complemento')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('complemento')->nullable()->after('numero');
            });
        }

        /*
        |--------------------------------------------------------------------------
        | CURSOS
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('cursos')) {
            Schema::create('cursos', function (Blueprint $table) {
                $table->id();
                $table->string('titulo');
                $table->text('descricao')->nullable();
                $table->string('capa')->nullable();
                $table->string('categoria')->nullable();
                $table->string('nivel')->default('Básico');
                $table->integer('duracao_total')->nullable();
                $table->decimal('valor', 10, 2)->nullable();
                $table->boolean('ativo')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | MODULOS
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('modulos')) {
            Schema::create('modulos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('curso_id')->constrained()->cascadeOnDelete();
                $table->string('titulo');
                $table->text('descricao')->nullable();
                $table->integer('ordem')->default(0);
                $table->timestamps();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | AULAS
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('aulas')) {
            Schema::create('aulas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('modulo_id')->constrained()->cascadeOnDelete();
                $table->string('titulo');
                $table->text('descricao')->nullable();
                $table->string('video_url')->nullable();
                $table->string('duracao')->nullable();
                $table->json('materiais')->nullable();
                $table->integer('ordem')->default(0);
                $table->boolean('liberada')->default(true);
                $table->timestamps();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | MATRICULAS
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('matriculas')) {
            Schema::create('matriculas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('aluno_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('curso_id')->constrained()->cascadeOnDelete();
                $table->date('data_matricula')->default(now());
                $table->date('data_validade')->nullable();
                $table->enum('status', ['ativo', 'concluido', 'cancelado', 'expirado'])->default('ativo');
                $table->timestamps();
                $table->unique(['aluno_id', 'curso_id']);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | PROGRESSO DAS AULAS
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('aula_progresso')) {
            Schema::create('aula_progresso', function (Blueprint $table) {
                $table->id();
                $table->foreignId('aluno_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('aula_id')->constrained()->cascadeOnDelete();
                $table->boolean('concluida')->default(false);
                $table->timestamp('ultima_visualizacao')->nullable();
                $table->integer('tempo_assistido')->default(0);
                $table->timestamps();
                $table->unique(['aluno_id', 'aula_id']);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | COMENTÁRIOS
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('comentarios_aula')) {
            Schema::create('comentarios_aula', function (Blueprint $table) {
                $table->id();
                $table->foreignId('aula_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('parent_id')
                    ->nullable()
                    ->constrained('comentarios_aula')
                    ->cascadeOnDelete();
                $table->text('comentario');
                $table->boolean('aprovado')->default(true);
                $table->timestamps();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | NOTAS
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('notas')) {
            Schema::create('notas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('aluno_id')->constrained('users')->cascadeOnDelete();
                $table->string('disciplina');
                $table->string('periodo');
                $table->string('professor')->nullable();
                $table->decimal('nota_av1', 4, 2)->nullable();
                $table->decimal('nota_av2', 4, 2)->nullable();
                $table->decimal('nota_av3', 4, 2)->nullable();
                $table->decimal('media_final', 4, 2)->default(0);
                $table->integer('faltas')->default(0);
                $table->enum('situacao', [
                    'Cursando',
                    'Aprovado',
                    'Reprovado',
                    'Reprovado por Falta'
                ])->default('Cursando');
                $table->text('observacoes')->nullable();
                $table->timestamps();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | FINANCEIRO
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('financeiro')) {
            Schema::create('financeiro', function (Blueprint $table) {
                $table->id();
                $table->foreignId('aluno_id')->constrained('users')->cascadeOnDelete();
                $table->string('referencia');
                $table->text('descricao')->nullable();
                $table->decimal('valor', 10, 2);
                $table->date('vencimento');
                $table->date('data_pagamento')->nullable();
                $table->enum('status', ['pendente', 'pago', 'vencido', 'cancelado'])->default('pendente');
                $table->enum('forma_pagamento', ['boleto', 'pix', 'cartao', 'dinheiro'])->nullable();
                $table->string('boleto_url')->nullable();
                $table->string('pix_copia_cola')->nullable();
                $table->string('comprovante')->nullable();
                $table->text('observacoes')->nullable();
                $table->timestamps();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | AULAS AO VIVO
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('aulas_ao_vivo')) {
            Schema::create('aulas_ao_vivo', function (Blueprint $table) {
                $table->id();
                $table->foreignId('curso_id')->constrained()->cascadeOnDelete();
                $table->string('titulo');
                $table->text('descricao')->nullable();
                $table->dateTime('data_hora');
                $table->string('link_transmissao')->nullable();
                $table->integer('duracao')->default(60);
                $table->boolean('gravacao_disponivel')->default(false);
                $table->string('gravacao_url')->nullable();
                $table->timestamps();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | AVISOS
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('avisos')) {
            Schema::create('avisos', function (Blueprint $table) {
                $table->id();
                $table->string('titulo');
                $table->text('mensagem');
                $table->enum('tipo', ['info', 'alerta', 'sucesso', 'urgente'])->default('info');
                $table->boolean('ativo')->default(true);
                $table->date('data_expiracao')->nullable();
                $table->timestamps();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | CERTIFICADOS
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('certificados')) {
            Schema::create('certificados', function (Blueprint $table) {
                $table->id();
                $table->foreignId('aluno_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('curso_id')->constrained()->cascadeOnDelete();
                $table->string('codigo_verificacao')->unique();
                $table->date('data_conclusao');
                $table->date('data_emissao')->default(now());
                $table->string('arquivo')->nullable();
                $table->integer('carga_horaria');
                $table->timestamps();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | REQUERIMENTOS
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('requerimentos')) {
            Schema::create('requerimentos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('aluno_id')->constrained('users')->cascadeOnDelete();
                $table->enum('tipo', [
                    'declaracao_matricula',
                    'historico_escolar',
                    'revisao_prova',
                    'troca_turma',
                    'outro'
                ]);
                $table->string('assunto');
                $table->text('descricao');
                $table->json('anexos')->nullable();
                $table->enum('status', ['pendente', 'em_analise', 'resolvido', 'negado'])->default('pendente');
                $table->text('resposta')->nullable();
                $table->foreignId('respondido_por')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('respondido_em')->nullable();
                $table->timestamps();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | BIBLIOTECA
        |--------------------------------------------------------------------------
        */
        if (!Schema::hasTable('biblioteca')) {
            Schema::create('biblioteca', function (Blueprint $table) {
                $table->id();
                $table->string('titulo');
                $table->text('descricao')->nullable();
                $table->string('categoria');
                $table->string('arquivo');
                $table->boolean('publico')->default(true);
                $table->integer('downloads')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('biblioteca');
        Schema::dropIfExists('requerimentos');
        Schema::dropIfExists('certificados');
        Schema::dropIfExists('avisos');
        Schema::dropIfExists('aulas_ao_vivo');
        Schema::dropIfExists('financeiro');
        Schema::dropIfExists('notas');
        Schema::dropIfExists('comentarios_aula');
        Schema::dropIfExists('aula_progresso');
        Schema::dropIfExists('matriculas');
        Schema::dropIfExists('aulas');
        Schema::dropIfExists('modulos');
        Schema::dropIfExists('cursos');
    }
};
