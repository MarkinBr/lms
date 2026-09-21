<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'nivel')) {
            foreach (['admin', 'secretario', 'professor', 'aluno'] as $nivel) {
                DB::table('users')->whereRaw('LOWER(nivel) = ?', [$nivel])->update(['nivel' => $nivel]);
            }

            Schema::table('users', function ($table) {
                $table->string('nivel')->default('professor')->change();
            });
        }

        if (Schema::hasTable('matriculas') && Schema::hasColumn('matriculas', 'status')) {
            $mapa = [
                'ativo' => ['ativo', 'ativa'],
                'trancado' => ['trancado', 'trancada'],
                'concluido' => ['concluido', 'concluida', 'concluída'],
                'cancelado' => ['cancelado', 'cancelada'],
            ];

            foreach ($mapa as $canonico => $variantes) {
                $placeholders = implode(',', array_fill(0, count($variantes), '?'));
                DB::update(
                    "UPDATE matriculas SET status = ? WHERE LOWER(COALESCE(status, '')) IN ({$placeholders})",
                    array_merge([$canonico], $variantes),
                );
            }
        }
    }

    public function down(): void
    {
        // Normalização de dados é intencionalmente irreversível.
    }
};
