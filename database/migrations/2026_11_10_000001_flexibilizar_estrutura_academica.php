<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // O índice parcial anterior restringia toda a instituição a um período ativo.
        if (in_array(DB::connection()->getDriverName(), ['pgsql', 'sqlite'], true)) {
            DB::statement('DROP INDEX IF EXISTS periodo_letivos_ativo_unique');
        }

        Schema::create('turma_periodos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turma_id')->constrained('turmas')->restrictOnDelete();
            $table->foreignId('periodo_letivo_id')->constrained('periodo_letivos')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['turma_id', 'periodo_letivo_id']);
        });

        Schema::create('turma_disciplinas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turma_id')->constrained('turmas')->restrictOnDelete();
            $table->foreignId('disciplina_id')->constrained('disciplinas')->restrictOnDelete();
            $table->foreignId('periodo_letivo_id')->nullable()->constrained('periodo_letivos')->restrictOnDelete();
            $table->unsignedInteger('ordem')->default(1);
            $table->boolean('ativa')->default(true);
            $table->timestamps();
            $table->unique(['turma_id', 'disciplina_id']);
        });

        // Mantém disciplinas.modulo_id como módulo principal/legado.
        // A associação adicional é N:N; não duplica aulas ou conteúdo curricular.
        Schema::create('disciplina_modulo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disciplina_id')->constrained('disciplinas')->restrictOnDelete();
            $table->foreignId('modulo_id')->constrained('modulos')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['disciplina_id', 'modulo_id']);
        });

        Schema::create('professor_atribuicoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turma_id')->constrained('turmas')->restrictOnDelete();
            $table->foreignId('professor_id')->constrained('users')->restrictOnDelete();
            $table->string('escopo', 20); // turma, disciplina, modulo, aula, instrumento
            $table->foreignId('disciplina_id')->nullable()->constrained('disciplinas')->restrictOnDelete();
            $table->foreignId('modulo_id')->nullable()->constrained('modulos')->restrictOnDelete();
            $table->foreignId('aula_id')->nullable()->constrained('aulas')->restrictOnDelete();
            $table->foreignId('instrumento_id')->nullable()->constrained('instrumentos_avaliacao')->restrictOnDelete();
            $table->json('permissoes');
            $table->boolean('ativa')->default(true);
            $table->timestamps();
            $table->index(['professor_id', 'ativa', 'turma_id'], 'prof_atrib_prof_ativa_turma_idx');
        });

        Schema::table('turmas', function (Blueprint $table) {
            $table->string('situacao_academica', 24)->default('planejada');
            $table->string('situacao_inscricoes', 24)->default('nao_iniciadas');
            $table->boolean('publicada')->default(false);
            $table->date('inscricoes_inicio')->nullable();
            $table->date('inscricoes_fim')->nullable();
        });

        // Migração aditiva: preserva todos os vínculos anteriores e os campos legados.
        $agora = now();
        DB::table('turmas')->orderBy('id')->chunkById(200, function ($turmas) use ($agora): void {
            foreach ($turmas as $turma) {
                if ($turma->periodo_letivo_id) {
                    DB::table('turma_periodos')->insert([
                        'turma_id' => $turma->id,
                        'periodo_letivo_id' => $turma->periodo_letivo_id,
                        'created_at' => $agora, 'updated_at' => $agora,
                    ]);
                }

                if ($turma->disciplina_id) {
                    DB::table('turma_disciplinas')->insert([
                        'turma_id' => $turma->id,
                        'disciplina_id' => $turma->disciplina_id,
                        'ordem' => 1, 'ativa' => true,
                        'created_at' => $agora, 'updated_at' => $agora,
                    ]);
                }

                if ($turma->professor_id) {
                    DB::table('professor_atribuicoes')->insert([
                        'turma_id' => $turma->id,
                        'professor_id' => $turma->professor_id,
                        'escopo' => 'turma',
                        'permissoes' => json_encode([
                            'visualizar', 'editar_conteudo', 'gerenciar_atividades',
                            'corrigir', 'lancar_notas',
                        ]),
                        'ativa' => true,
                        'created_at' => $agora, 'updated_at' => $agora,
                    ]);
                }

                $academica = match ($turma->status) {
                    'aberta' => 'em_andamento',
                    'encerrada' => 'concluida',
                    'cancelada' => 'cancelada',
                    default => 'planejada',
                };
                DB::table('turmas')->where('id', $turma->id)->update([
                    'situacao_academica' => $academica,
                    'situacao_inscricoes' => $turma->status === 'aberta' ? 'abertas' : 'nao_iniciadas',
                    'publicada' => $turma->status === 'aberta',
                ]);
            }
        });

        DB::table('disciplinas')->whereNotNull('modulo_id')->orderBy('id')
            ->chunkById(200, function ($disciplinas) use ($agora): void {
                foreach ($disciplinas as $disciplina) {
                    DB::table('disciplina_modulo')->insert([
                        'disciplina_id' => $disciplina->id,
                        'modulo_id' => $disciplina->modulo_id,
                        'created_at' => $agora, 'updated_at' => $agora,
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Atenção: rollback elimina APENAS os novos vínculos. Realize backup antes.
        Schema::dropIfExists('professor_atribuicoes');
        Schema::dropIfExists('disciplina_modulo');
        Schema::dropIfExists('turma_disciplinas');
        Schema::dropIfExists('turma_periodos');

        Schema::table('turmas', function (Blueprint $table) {
            $table->dropColumn([
                'situacao_academica', 'situacao_inscricoes', 'publicada',
                'inscricoes_inicio', 'inscricoes_fim',
            ]);
        });

        // Não recria o índice exclusivo se houver múltiplos períodos ativos.
    }
};
