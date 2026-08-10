<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Remover a dependência na tabela MATRICULAS
        Schema::table('matriculas', function (Blueprint $table) {
            // Primeiro remove a chave estrangeira para evitar o CASCADE DELETE
            $table->dropForeign(['aluno_id']); 
            // Depois pode remover a coluna (opcional agora, mas recomendado)
            $table->dropColumn('aluno_id');
        });

        // 2. Remover a dependência na tabela FINANCEIRO
        Schema::table('financeiro', function (Blueprint $table) {
            $table->dropForeign(['aluno_id']);
            $table->dropColumn('aluno_id');
        });

        // 3. Remover a dependência na tabela NOTAS
        Schema::table('notas', function (Blueprint $table) {
            $table->dropForeign(['aluno_id']);
            $table->dropColumn('aluno_id');
        });
        
        // Repita para requerimentos, certificados, etc... se houver vínculo.
    }

    public function down(): void
    {
        // Não precisamos reverter isso, pois é uma limpeza definitiva.
    }
};