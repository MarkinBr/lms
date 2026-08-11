<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('turmas')) {
            return;
        }

        if (! Schema::hasColumn('turmas', 'periodo_letivo_id')) {
            Schema::table('turmas', function (Blueprint $table) {
                $table->foreignId('periodo_letivo_id')
                    ->nullable()
                    ->constrained('periodo_letivos')
                    ->restrictOnDelete();
            });
        }

        if (! Schema::hasColumn('turmas', 'disciplina_id')) {
            Schema::table('turmas', function (Blueprint $table) {
                $table->foreignId('disciplina_id')
                    ->nullable()
                    ->constrained('disciplinas')
                    ->restrictOnDelete();
            });
        }

        if (! Schema::hasColumn('turmas', 'professor_id')) {
            Schema::table('turmas', function (Blueprint $table) {
                $table->foreignId('professor_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('turmas', 'codigo')) {
            Schema::table('turmas', function (Blueprint $table) {
                $table->string('codigo')->nullable();
            });
        }

        if (! Schema::hasColumn('turmas', 'capacidade')) {
            Schema::table('turmas', function (Blueprint $table) {
                $table->unsignedInteger('capacidade')->nullable();
            });
        }

        if (! Schema::hasColumn('turmas', 'modalidade')) {
            Schema::table('turmas', function (Blueprint $table) {
                $table->string('modalidade')->nullable();
            });
        }

        if (! Schema::hasColumn('turmas', 'status')) {
            Schema::table('turmas', function (Blueprint $table) {
                $table->string('status')->nullable()->default('planejada');
            });
        }

        DB::table('turmas')
            ->whereNull('status')
            ->update(['status' => 'planejada']);

        DB::table('turmas')
            ->where('ativa', false)
            ->where('status', 'planejada')
            ->update(['status' => 'encerrada']);

        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement('CREATE INDEX IF NOT EXISTS turmas_periodo_letivo_id_index ON turmas (periodo_letivo_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS turmas_disciplina_id_index ON turmas (disciplina_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS turmas_professor_id_index ON turmas (professor_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS turmas_status_index ON turmas (status)');

            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS turmas_periodo_codigo_unique ON turmas (periodo_letivo_id, codigo) WHERE periodo_letivo_id IS NOT NULL AND codigo IS NOT NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('turmas')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement('DROP INDEX IF EXISTS turmas_periodo_codigo_unique');
            DB::statement('DROP INDEX IF EXISTS turmas_status_index');
            DB::statement('DROP INDEX IF EXISTS turmas_professor_id_index');
            DB::statement('DROP INDEX IF EXISTS turmas_disciplina_id_index');
            DB::statement('DROP INDEX IF EXISTS turmas_periodo_letivo_id_index');
        }

        $colunas = [
            'periodo_letivo_id',
            'disciplina_id',
            'professor_id',
            'codigo',
            'capacidade',
            'modalidade',
            'status',
        ];

        foreach ($colunas as $coluna) {
            if (! Schema::hasColumn('turmas', $coluna)) {
                continue;
            }

            try {
                Schema::table('turmas', function (Blueprint $table) use ($coluna) {
                    $table->dropForeign([$coluna]);
                });
            } catch (Throwable $e) {
                // FK pode não existir em SQLite/ambiente legado.
            }
        }

        foreach ($colunas as $coluna) {
            if (! Schema::hasColumn('turmas', $coluna)) {
                continue;
            }

            try {
                Schema::table('turmas', function (Blueprint $table) use ($coluna) {
                    $table->dropColumn($coluna);
                });
            } catch (Throwable $e) {
                // Em SQLite, algumas remoções podem exigir recriação manual.
            }
        }
    }
};
