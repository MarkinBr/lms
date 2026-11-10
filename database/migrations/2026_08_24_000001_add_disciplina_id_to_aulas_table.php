<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Issue #57 — vínculo opcional aulas.disciplina_id.
 *
 * Decisões registradas em DECISOES.md:
 * - coluna nullable nesta Issue (compatibilidade com aulas legadas);
 * - sem backfill heurístico;
 * - FK com restrictOnDelete para proteger histórico acadêmico;
 * - endurecimento de nullability fica para Issue posterior, após auditoria
 *   dos dados de produção.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('aulas', 'disciplina_id')) {
            return;
        }

        $this->semViewEstatisticasNoSqlite(function (): void {
            Schema::table('aulas', function (Blueprint $table): void {
                $table->foreignId('disciplina_id')
                    ->nullable()
                    ->after('modulo_id')
                    ->constrained('disciplinas')
                    ->restrictOnDelete();

                $table->index('disciplina_id');
            });
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('aulas', 'disciplina_id')) {
            return;
        }

        $this->semViewEstatisticasNoSqlite(function (): void {
            Schema::table('aulas', function (Blueprint $table): void {
                $table->dropForeign(['disciplina_id']);
                $table->dropIndex(['disciplina_id']);
                $table->dropColumn('disciplina_id');
            });
        });
    }

    /**
     * O SQLite reconstrói a tabela ao adicionar/remover a FK. A view criada
     * por uma migration anterior referencia `aulas` e impediria o rename da
     * tabela temporária. PostgreSQL não precisa deste contorno.
     */
    private function semViewEstatisticasNoSqlite(callable $operacao): void
    {
        $sqlite = DB::connection()->getDriverName() === 'sqlite';
        $viewSql = null;

        if ($sqlite) {
            $viewSql = DB::table('sqlite_master')
                ->where('type', 'view')
                ->where('name', 'v_curso_estatisticas')
                ->value('sql');

            if (is_string($viewSql) && $viewSql !== '') {
                DB::statement('DROP VIEW v_curso_estatisticas');
            }
        }

        try {
            $operacao();
        } finally {
            if ($sqlite && is_string($viewSql) && $viewSql !== '') {
                DB::statement($viewSql);
            }
        }
    }
};
