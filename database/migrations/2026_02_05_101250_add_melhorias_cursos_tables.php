<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Adicionar campos na tabela CURSOS
        Schema::table('cursos', function (Blueprint $table) {
            if (!Schema::hasColumn('cursos', 'thumbnail')) {
                $table->string('thumbnail')->nullable()->after('capa');
            }
            if (!Schema::hasColumn('cursos', 'video_preview')) {
                $table->string('video_preview')->nullable()->comment('URL de vídeo de apresentação do curso')->after('thumbnail');
            }
            if (!Schema::hasColumn('cursos', 'instrutor_id')) {
                $table->unsignedBigInteger('instrutor_id')->nullable()->index()->after('categoria');
            }
            if (!Schema::hasColumn('cursos', 'certificado_habilitado')) {
                $table->boolean('certificado_habilitado')->default(true)->comment('Se habilitado, emite certificado automático');
            }
            if (!Schema::hasColumn('cursos', 'carga_horaria')) {
                $table->integer('carga_horaria')->nullable()->comment('Carga horária para o certificado');
            }
            if (!Schema::hasColumn('cursos', 'ordem_exibicao')) {
                $table->integer('ordem_exibicao')->default(0)->index()->after('ativo');
            }
        });

        // 2. Adicionar campos na tabela AULAS
        Schema::table('aulas', function (Blueprint $table) {
            if (!Schema::hasColumn('aulas', 'data_liberacao')) {
                $table->dateTime('data_liberacao')->nullable()->comment('Data automática de liberação da aula')->after('liberada');
            }
            if (!Schema::hasColumn('aulas', 'tipo_liberacao')) {
                $table->enum('tipo_liberacao', ['imediata', 'agendada', 'sequencial'])->default('imediata')->after('data_liberacao');
            }
            if (!Schema::hasColumn('aulas', 'tipo_conteudo')) {
                $table->enum('tipo_conteudo', ['video', 'texto', 'quiz', 'misto'])->default('video')->after('descricao');
            }
            if (!Schema::hasColumn('aulas', 'conteudo_texto')) {
                $table->longText('conteudo_texto')->nullable()->after('descricao');
            }
        });

        // 3. Criar tabela de QUIZ
        if (!Schema::hasTable('aula_quiz')) {
            Schema::create('aula_quiz', function (Blueprint $table) {
                $table->id();
                $table->foreignId('aula_id')->constrained('aulas')->onDelete('cascade');
                $table->string('titulo');
                $table->text('descricao')->nullable();
                $table->decimal('nota_minima', 3, 1)->default(7.0)->comment('Nota mínima para aprovação');
                $table->integer('tentativas_maximas')->default(3)->comment('Número máximo de tentativas, 0 = ilimitado');
                $table->integer('tempo_limite')->nullable()->comment('Tempo limite em minutos, NULL = sem limite');
                $table->json('questoes')->nullable();
                $table->boolean('ativo')->default(true);
                $table->timestamps();
            });
        }

        // 4. Criar tabela de RESPOSTAS DO QUIZ
        if (!Schema::hasTable('quiz_respostas')) {
            Schema::create('quiz_respostas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quiz_id')->constrained('aula_quiz')->onDelete('cascade');
                $table->foreignId('aluno_id')->constrained('users')->onDelete('cascade');
                $table->json('respostas')->nullable();
                $table->decimal('nota', 4, 2)->nullable();
                $table->boolean('aprovado')->default(false);
                $table->integer('tentativa')->default(1);
                $table->integer('tempo_gasto')->nullable()->comment('Tempo gasto em segundos');
                $table->timestamps();
                
                $table->index(['quiz_id', 'aluno_id']);
            });
        }

        // 5. Criar tabela de ANOTAÇÕES DO ALUNO
        if (!Schema::hasTable('aula_anotacoes')) {
            Schema::create('aula_anotacoes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('aula_id')->constrained('aulas')->onDelete('cascade');
                $table->foreignId('aluno_id')->constrained('users')->onDelete('cascade');
                $table->integer('tempo_video')->nullable()->comment('Timestamp do vídeo em segundos');
                $table->text('anotacao');
                $table->timestamps();

                $table->index(['aula_id', 'aluno_id']);
            });
        }

        // 6. Criar tabela de AVALIAÇÕES DO CURSO
        if (!Schema::hasTable('curso_avaliacoes')) {
            Schema::create('curso_avaliacoes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('curso_id')->constrained('cursos')->onDelete('cascade');
                $table->foreignId('aluno_id')->constrained('users')->onDelete('cascade');
                $table->integer('nota')->comment('Nota de 1 a 5');
                $table->text('comentario')->nullable();
                $table->boolean('aprovado')->default(true)->comment('Se a avaliação foi aprovada pela moderação');
                $table->timestamps();

                $table->unique(['curso_id', 'aluno_id']);
                $table->index('nota');
            });
        }

        // 7. Criar tabela de TAGS
        if (!Schema::hasTable('curso_tags')) {
            Schema::create('curso_tags', function (Blueprint $table) {
                $table->id();
                $table->string('nome', 100);
                $table->string('slug', 100)->unique();
                $table->timestamps();
            });
        }

        // 8. Criar tabela PIVOT TAGS
        if (!Schema::hasTable('curso_tag_pivot')) {
            Schema::create('curso_tag_pivot', function (Blueprint $table) {
                $table->foreignId('curso_id')->constrained('cursos')->onDelete('cascade');
                $table->foreignId('tag_id')->constrained('curso_tags')->onDelete('cascade');
                $table->primary(['curso_id', 'tag_id']);
            });
        }

        // 9. Adicionar Índices Extras
        try {
            Schema::table('aula_progresso', function (Blueprint $table) {
                $table->index(['aluno_id', 'concluida']); 
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('aula_progresso', function (Blueprint $table) {
                $table->index(['aula_id', 'concluida']);
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('matriculas', function (Blueprint $table) {
                $table->index(['user_id', 'status']); 
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('matriculas', function (Blueprint $table) {
                $table->index(['curso_id', 'status']);
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('aulas', function (Blueprint $table) {
                $table->index(['modulo_id', 'ordem']);
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('aulas', function (Blueprint $table) {
                $table->index(['modulo_id', 'liberada']);
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('modulos', function (Blueprint $table) {
                $table->index(['curso_id', 'ordem']);
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('aulas_ao_vivo', function (Blueprint $table) {
                $table->index(['curso_id', 'data_hora']);
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('aulas_ao_vivo', function (Blueprint $table) {
                $table->index('data_hora');
            });
        } catch (\Throwable $e) {}

        // 10. Inserir Tags Iniciais
        if (Schema::hasTable('curso_tags') && DB::table('curso_tags')->count() == 0) {
            $tags = [
                ['nome' => 'Teologia', 'slug' => 'teologia', 'created_at' => now(), 'updated_at' => now()],
                ['nome' => 'Bíblia', 'slug' => 'biblia', 'created_at' => now(), 'updated_at' => now()],
                ['nome' => 'Liderança', 'slug' => 'lideranca', 'created_at' => now(), 'updated_at' => now()],
                ['nome' => 'Adoração', 'slug' => 'adoracao', 'created_at' => now(), 'updated_at' => now()],
                ['nome' => 'Missões', 'slug' => 'missoes', 'created_at' => now(), 'updated_at' => now()],
            ];
            DB::table('curso_tags')->insert($tags);
        }

        // 11. Criar Views (Compatível com SQLite e MySQL)
        DB::statement("DROP VIEW IF EXISTS v_curso_estatisticas");
        DB::statement("
            CREATE VIEW v_curso_estatisticas AS
            SELECT 
                c.id AS curso_id,
                c.titulo AS curso_titulo,
                COUNT(DISTINCT m.id) AS total_matriculas,
                COUNT(DISTINCT CASE WHEN m.status = 'ativo' THEN m.id END) AS matriculas_ativas,
                COUNT(DISTINCT CASE WHEN m.status = 'concluido' THEN m.id END) AS matriculas_concluidas,
                COUNT(DISTINCT md.id) AS total_modulos,
                COUNT(DISTINCT a.id) AS total_aulas,
                AVG(ca.nota) AS avaliacao_media,
                COUNT(DISTINCT ca.id) AS total_avaliacoes
            FROM cursos c
            LEFT JOIN matriculas m ON c.id = m.curso_id
            LEFT JOIN modulos md ON c.id = md.curso_id
            LEFT JOIN aulas a ON md.id = a.modulo_id
            LEFT JOIN curso_avaliacoes ca ON c.id = ca.curso_id
            GROUP BY c.id, c.titulo;
        ");

        // Stored Procedure/Function para calcular progresso do curso
        // Compatível com PostgreSQL (PL/pgSQL)
        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::unprepared("
                CREATE OR REPLACE FUNCTION sp_calcular_progresso_curso(
                    p_aluno_id BIGINT,
                    p_curso_id BIGINT
                )
                RETURNS DECIMAL(5,2) AS \$\$
                DECLARE
                    v_total_aulas INT;
                    v_aulas_concluidas INT;
                    v_progresso DECIMAL(5,2);
                BEGIN
                    SELECT COUNT(*)
                    INTO v_total_aulas
                    FROM aulas a
                    INNER JOIN modulos md ON a.modulo_id = md.id
                    WHERE md.curso_id = p_curso_id;

                    SELECT COUNT(*)
                    INTO v_aulas_concluidas
                    FROM aula_progresso ap
                    INNER JOIN aulas a ON ap.aula_id = a.id
                    INNER JOIN modulos md ON a.modulo_id = md.id
                    WHERE md.curso_id = p_curso_id
                      AND ap.aluno_id = p_aluno_id
                      AND ap.concluida = TRUE;

                    IF v_total_aulas > 0 THEN
                        v_progresso := (v_aulas_concluidas::DECIMAL / v_total_aulas) * 100;
                    ELSE
                        v_progresso := 0;
                    END IF;

                    RETURN v_progresso;
                END;
                \$\$ LANGUAGE plpgsql;
            ");
        } elseif ($driver === 'mysql') {
            DB::unprepared("
                DROP PROCEDURE IF EXISTS sp_calcular_progresso_curso;
                CREATE PROCEDURE sp_calcular_progresso_curso(
                    IN p_aluno_id BIGINT,
                    IN p_curso_id BIGINT,
                    OUT p_progresso DECIMAL(5,2)
                )
                BEGIN
                    DECLARE v_total_aulas INT;
                    DECLARE v_aulas_concluidas INT;

                    SELECT COUNT(*)
                    INTO v_total_aulas
                    FROM aulas a
                    INNER JOIN modulos md ON a.modulo_id = md.id
                    WHERE md.curso_id = p_curso_id;

                    SELECT COUNT(*)
                    INTO v_aulas_concluidas
                    FROM aula_progresso ap
                    INNER JOIN aulas a ON ap.aula_id = a.id
                    INNER JOIN modulos md ON a.modulo_id = md.id
                    WHERE md.curso_id = p_curso_id
                      AND ap.aluno_id = p_aluno_id
                      AND ap.concluida = 1;

                    IF v_total_aulas > 0 THEN
                        SET p_progresso = (v_aulas_concluidas / v_total_aulas) * 100;
                    ELSE
                        SET p_progresso = 0;
                    END IF;
                END
            ");
        }
    }

    public function down(): void
    {
        // Reverter alterações (Dropar colunas e tabelas)
        Schema::table('cursos', function (Blueprint $table) {
            $table->dropColumn(['thumbnail', 'video_preview', 'instrutor_id', 'certificado_habilitado', 'carga_horaria', 'ordem_exibicao']);
        });

        Schema::table('aulas', function (Blueprint $table) {
            $table->dropColumn(['data_liberacao', 'tipo_liberacao', 'tipo_conteudo', 'conteudo_texto']);
        });

        Schema::dropIfExists('curso_tag_pivot');
        Schema::dropIfExists('curso_tags');
        Schema::dropIfExists('curso_avaliacoes');
        Schema::dropIfExists('aula_anotacoes');
        Schema::dropIfExists('quiz_respostas');
        Schema::dropIfExists('aula_quiz');

        DB::unprepared("DROP VIEW IF EXISTS v_curso_estatisticas");
        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::unprepared("DROP FUNCTION IF EXISTS sp_calcular_progresso_curso(BIGINT, BIGINT)");
        } elseif ($driver === 'mysql') {
            DB::unprepared("DROP PROCEDURE IF EXISTS sp_calcular_progresso_curso");
        }
    }
};