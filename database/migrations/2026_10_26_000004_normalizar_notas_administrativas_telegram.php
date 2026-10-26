<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notas')) {
            return;
        }

        if (! Schema::hasColumn('notas', 'user_id')) {
            Schema::table('notas', function (Blueprint $table): void {
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            });
        }

        $temAlunoLegado = Schema::hasColumn('notas', 'aluno_id');
        if ($temAlunoLegado) {
            // Preserva o vínculo canônico existente e recupera somente os ausentes.
            DB::table('notas')->whereNull('user_id')->update(['user_id' => DB::raw('aluno_id')]);
        }

        $situacoes = ['Cursando', 'Aprovado', 'Reprovado', 'Reprovado por Falta', 'Recuperação'];
        $postgres = DB::getDriverName() === 'pgsql';
        Schema::table('notas', function (Blueprint $table) use ($temAlunoLegado, $situacoes, $postgres): void {
            if ($temAlunoLegado) {
                $table->unsignedBigInteger('aluno_id')->nullable()->change();
            }
            if (! $postgres) {
                // No SQLite, o rebuild conserva os registros, índices e FKs.
                $table->enum('situacao', $situacoes)->default('Cursando')->change();
            }
        });

        if ($postgres) {
            // O enum do Laravel no PostgreSQL é varchar com CHECK, não um tipo enum nativo.
            DB::statement('ALTER TABLE notas DROP CONSTRAINT IF EXISTS notas_situacao_check');
            DB::statement("ALTER TABLE notas ADD CONSTRAINT notas_situacao_check CHECK (situacao IN ('Cursando', 'Aprovado', 'Reprovado', 'Reprovado por Falta', 'Recuperação'))");
        }
    }

    public function down(): void
    {
        // Correção do contrato legado: reverter o CHECK ou o backfill perderia
        // notas em recuperação ou vínculos recuperados. Mantém os dados válidos.
    }
};
