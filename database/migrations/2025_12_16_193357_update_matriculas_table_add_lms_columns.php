<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matriculas', function (Blueprint $table) {
            // Adiciona as colunas novas permitindo nulo inicialmente
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('curso_id')->nullable()->after('user_id')->constrained('cursos')->cascadeOnDelete();
            $table->date('data_validade')->nullable();
            
            // Permite que as colunas antigas fiquem nulas (para transição)
            $table->unsignedBigInteger('aluno_id')->nullable()->change();
            $table->unsignedBigInteger('turma_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('matriculas', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['curso_id']);
            $table->dropColumn(['user_id', 'curso_id', 'data_validade']);
        });
    }
};