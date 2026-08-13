<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Vincula a matrícula à inscrição (Application) que a originou.
 *
 * Regras:
 *  - Uma matrícula pode ter no máximo uma inscrição de origem
 *    (por isso `application_id` é UNIQUE).
 *  - Uma inscrição só pode dar origem a UMA matrícula (mesma constraint).
 *  - Índice composto (user_id, curso_id, status) suporta a checagem de
 *    "matrícula ativa duplicada" feita pelo AprovarInscricaoService.
 *
 * Compatível com SQLite (testes) e PostgreSQL (produção).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('matriculas')) {
            return;
        }

        $recriarViewCursoEstatisticas = $this->removerViewCursoEstatisticasParaSqlite();

        Schema::table('matriculas', function (Blueprint $table) {
            if (! Schema::hasColumn('matriculas', 'application_id')) {
                // UUID string (Application.id é uuid); nullable para permitir
                // matrículas antigas / criadas manualmente sem inscrição.
                $table->uuid('application_id')->nullable()->after('curso_id');
            }
        });

        // Unicidade: uma inscrição gera no máximo uma matrícula.
        // Cria em bloco separado para poder testar existência do índice depois.
        Schema::table('matriculas', function (Blueprint $table) {
            $table->unique('application_id', 'matriculas_application_id_unique');
        });

        // Índice composto para consultas de duplicidade de aluno/curso/status.
        Schema::table('matriculas', function (Blueprint $table) {
            $table->index(
                ['user_id', 'curso_id', 'status'],
                'matriculas_user_curso_status_index'
            );
        });

        // Foreign key para applications (somente se applications existir).
        if (Schema::hasTable('applications')) {
            Schema::table('matriculas', function (Blueprint $table) {
                $table->foreign('application_id', 'matriculas_application_id_foreign')
                    ->references('id')->on('applications')
                    ->nullOnDelete();
            });
        }

        $this->restaurarViewCursoEstatisticasParaSqlite($recriarViewCursoEstatisticas);
    }

    public function down(): void
    {
        if (! Schema::hasTable('matriculas')) {
            return;
        }

        $recriarViewCursoEstatisticas = $this->removerViewCursoEstatisticasParaSqlite();

        Schema::table('matriculas', function (Blueprint $table) {
            try {
                $table->dropForeign('matriculas_application_id_foreign');
            } catch (\Throwable $e) {
                // FK pode não existir se applications não existia no up().
            }
        });

        Schema::table('matriculas', function (Blueprint $table) {
            try {
                $table->dropUnique('matriculas_application_id_unique');
            } catch (\Throwable $e) {
                // ignore
            }
            try {
                $table->dropIndex('matriculas_user_curso_status_index');
            } catch (\Throwable $e) {
                // ignore
            }
        });

        Schema::table('matriculas', function (Blueprint $table) {
            if (Schema::hasColumn('matriculas', 'application_id')) {
                $table->dropColumn('application_id');
            }
        });

        $this->restaurarViewCursoEstatisticasParaSqlite($recriarViewCursoEstatisticas);
    }

    /**
     * No SQLite, alterar uma tabela recria sua estrutura. A view legada que
     * referencia matriculas precisa ser removida antes e restaurada depois.
     */
    private function removerViewCursoEstatisticasParaSqlite(): bool
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return false;
        }

        $viewExiste = DB::table('sqlite_master')
            ->where('type', 'view')
            ->where('name', 'v_curso_estatisticas')
            ->exists();

        if ($viewExiste) {
            DB::statement('DROP VIEW v_curso_estatisticas');
        }

        return $viewExiste;
    }

    private function restaurarViewCursoEstatisticasParaSqlite(bool $restaurar): void
    {
        if (! $restaurar) {
            return;
        }

        DB::statement(<<<'SQL'
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
            GROUP BY c.id, c.titulo
        SQL);
    }
};
