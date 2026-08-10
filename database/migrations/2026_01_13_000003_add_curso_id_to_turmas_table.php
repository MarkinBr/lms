<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Verifica se a tabela existe e se a coluna NÃO existe
        if (Schema::hasTable('turmas') && !Schema::hasColumn('turmas', 'curso_id')) {
            Schema::table('turmas', function (Blueprint $table) {
                $table->foreignId('curso_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('cursos')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('turmas') && Schema::hasColumn('turmas', 'curso_id')) {
            Schema::table('turmas', function (Blueprint $table) {
                $table->dropForeign(['curso_id']);
                $table->dropColumn('curso_id');
            });
        }
    }
};