<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. CORREÇÃO CRÍTICA (Erro atual do calendário)
        Schema::table('aulas_ao_vivo', function (Blueprint $table) {
            if (!Schema::hasColumn('aulas_ao_vivo', 'turma_id')) {
                $table->foreignId('turma_id')->nullable()->after('curso_id')->constrained('turmas')->onDelete('cascade');
            }
        });

        // 2. Tabela Users (Campos extras)
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'telefone' => ['string', 20],
                'data_nascimento' => ['date', null],
                'cpf' => ['string', 14],
                'endereco' => ['string', 255],
                'cidade' => ['string', 100],
                'estado' => ['string', 2],
                'cep' => ['string', 9],
                'foto' => ['string', 255],
                'ultima_visualizacao_avisos' => ['timestamp', null],
            ];

            foreach ($columns as $name => $type) {
                if (!Schema::hasColumn('users', $name)) {
                    if ($type[0] === 'string') {
                        $table->string($name, $type[1] ?? 255)->nullable();
                    } elseif ($type[0] === 'date') {
                        $table->date($name)->nullable();
                    } elseif ($type[0] === 'timestamp') {
                        $table->timestamp($name)->nullable();
                    }
                }
            }
        });

        // 3. Tabela Avisos
        Schema::table('avisos', function (Blueprint $table) {
            if (!Schema::hasColumn('avisos', 'data_evento')) {
                $table->date('data_evento')->nullable()->after('data_expiracao');
            }
            if (!Schema::hasColumn('avisos', 'anexo')) {
                $table->string('anexo')->nullable()->after('mensagem');
            }
        });

        // 4. Tabela Mensagens (Criar se não existir)
        if (!Schema::hasTable('mensagens')) {
            Schema::create('mensagens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('remetente_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('destinatario_id')->constrained('users')->onDelete('cascade');
                $table->string('assunto');
                $table->text('conteudo');
                $table->boolean('lida')->default(false);
                $table->timestamp('data_leitura')->nullable();
                $table->timestamps();
            });
        }

        // 5. Tabela Requerimentos (Ajustar ou Criar)
        if (!Schema::hasTable('requerimentos')) {
            Schema::create('requerimentos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('aluno_id')->constrained('users')->onDelete('cascade');
                $table->string('protocolo')->unique();
                $table->string('tipo');
                $table->string('assunto');
                $table->text('descricao');
                $table->string('anexo')->nullable();
                $table->enum('status', ['pendente', 'em_analise', 'aprovado', 'negado', 'cancelado'])->default('pendente');
                $table->text('resposta')->nullable();
                $table->timestamp('data_resposta')->nullable();
                $table->foreignId('respondido_por')->nullable()->constrained('users');
                $table->timestamps();
            });
        } else {
            Schema::table('requerimentos', function (Blueprint $table) {
                if (!Schema::hasColumn('requerimentos', 'protocolo')) $table->string('protocolo')->nullable()->after('id');
                if (!Schema::hasColumn('requerimentos', 'anexo')) $table->string('anexo')->nullable();
                if (!Schema::hasColumn('requerimentos', 'resposta')) $table->text('resposta')->nullable();
            });
        }

        // 6. Tabela Certificados
        Schema::table('certificados', function (Blueprint $table) {
            if (!Schema::hasColumn('certificados', 'matricula_id')) {
                $table->foreignId('matricula_id')->nullable()->constrained('matriculas')->onDelete('cascade');
            }
            if (!Schema::hasColumn('certificados', 'carga_horaria')) {
                $table->integer('carga_horaria')->nullable();
            }
        });

        // 7. Tabela Declarações
        Schema::table('declaracoes', function (Blueprint $table) {
            if (!Schema::hasColumn('declaracoes', 'tipo')) {
                $table->enum('tipo', ['matricula', 'frequencia', 'conclusao'])->nullable();
            }
            if (!Schema::hasColumn('declaracoes', 'finalidade')) {
                $table->string('finalidade')->nullable();
            }
            if (!Schema::hasColumn('declaracoes', 'status')) {
                $table->string('status')->default('emitida');
            }
        });

        // 8. Tabela Matrículas
        Schema::table('matriculas', function (Blueprint $table) {
            if (!Schema::hasColumn('matriculas', 'data_conclusao')) {
                $table->date('data_conclusao')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Reversão simplificada para evitar erros de chave estrangeira
        // Em produção, isso deveria ser mais detalhado.
    }
};