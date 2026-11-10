<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Issue #25 — Organizar aulas híbridas por turma.
     *
     * Adiciona apenas os campos efetivamente ausentes em `aulas_ao_vivo` para
     * suportar o encontro híbrido (modalidade, local, status, observacoes).
     *
     * Nota de segurança: o Model App\Models\AulaAoVivo e o Controller do
     * calendário do aluno já referenciam `turma_id`, mas nenhuma migration
     * disponível neste pacote de contexto efetivamente cria essa coluna na
     * tabela `aulas_ao_vivo` (ela existe em `eventos_calendario`, não aqui).
     * Para não deixar o recurso inutilizável caso essa lacuna também exista
     * no repositório real, a criação de `turma_id` abaixo é condicional
     * (Schema::hasColumn) e vira um no-op inofensivo se a coluna já existir.
     */
    public function up(): void
    {
        if (! Schema::hasTable('aulas_ao_vivo')) {
            return;
        }

        if (! Schema::hasColumn('aulas_ao_vivo', 'turma_id')) {
            Schema::table('aulas_ao_vivo', function (Blueprint $table) {
                $table->foreignId('turma_id')
                    ->nullable()
                    ->constrained('turmas')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('aulas_ao_vivo', 'modalidade')) {
            Schema::table('aulas_ao_vivo', function (Blueprint $table) {
                $table->string('modalidade')->nullable();
            });
        }

        if (! Schema::hasColumn('aulas_ao_vivo', 'local')) {
            Schema::table('aulas_ao_vivo', function (Blueprint $table) {
                $table->string('local')->nullable();
            });
        }

        if (! Schema::hasColumn('aulas_ao_vivo', 'status')) {
            Schema::table('aulas_ao_vivo', function (Blueprint $table) {
                $table->string('status')->nullable()->default('agendada');
            });
        }

        if (! Schema::hasColumn('aulas_ao_vivo', 'observacoes')) {
            Schema::table('aulas_ao_vivo', function (Blueprint $table) {
                $table->text('observacoes')->nullable();
            });
        }

        // Dados legados: nunca havia modalidade/status. Inferimos o mínimo
        // possível sem inventar local, link ou datas.
        DB::table('aulas_ao_vivo')->whereNull('status')->update(['status' => 'agendada']);

        DB::table('aulas_ao_vivo')
            ->whereNull('modalidade')
            ->whereNotNull('link_transmissao')
            ->update(['modalidade' => 'online']);

        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement('CREATE INDEX IF NOT EXISTS aulas_ao_vivo_turma_id_index ON aulas_ao_vivo (turma_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS aulas_ao_vivo_status_index ON aulas_ao_vivo (status)');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('aulas_ao_vivo')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement('DROP INDEX IF EXISTS aulas_ao_vivo_status_index');
            DB::statement('DROP INDEX IF EXISTS aulas_ao_vivo_turma_id_index');
        }

        foreach (['observacoes', 'status', 'local', 'modalidade'] as $coluna) {
            if (! Schema::hasColumn('aulas_ao_vivo', $coluna)) {
                continue;
            }

            try {
                Schema::table('aulas_ao_vivo', function (Blueprint $table) use ($coluna) {
                    $table->dropColumn($coluna);
                });
            } catch (Throwable $e) {
                // Ambiente legado pode não permitir remoção direta; segue sem interromper o rollback.
            }
        }

        // Propositalmente NÃO removemos `turma_id` aqui: não há como saber, em
        // rollback, se esta migration foi realmente quem a criou no ambiente
        // real (ela pode já existir via outra migration não incluída neste
        // pacote de contexto). Excluí-la por engano apagaria uma coluna usada
        // pelo Model/Resource/Controller já existentes. O pior caso é a
        // coluna permanecer após o rollback — nunca perder dados.
    }
};
