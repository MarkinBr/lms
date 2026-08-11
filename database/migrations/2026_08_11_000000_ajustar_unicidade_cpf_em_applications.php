<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Permite que o mesmo CPF se inscreva em cursos diferentes,
     * mantendo a impossibilidade de duplicar inscrição no mesmo curso:
     * - remove a unicidade global de `cpf`;
     * - cria unicidade composta de `cpf` + `curso_id`.
     */
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            // Remove a restrição UNIQUE global de CPF (nome padrão do Laravel)
            $table->dropUnique(['cpf']);

            // Garante que a mesma pessoa (CPF) não se inscreva duas vezes no mesmo curso
            $table->unique(['cpf', 'curso_id'], 'applications_cpf_curso_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Restaura a situação anterior: CPF globalmente único
     * e sem a unicidade composta por curso.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropUnique('applications_cpf_curso_unique');
            $table->unique('cpf');
        });
    }
};
