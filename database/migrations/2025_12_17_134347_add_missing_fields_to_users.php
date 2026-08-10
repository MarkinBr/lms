<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Campos para Professores (se ainda não existirem)
            if (!Schema::hasColumn('users', 'area_atuacao')) {
                $table->string('area_atuacao')->nullable();
                $table->text('biografia')->nullable();
            }

            // Campos para Alunos
            if (!Schema::hasColumn('users', 'responsavel_nome')) {
                $table->string('responsavel_nome')->nullable();
                $table->string('responsavel_telefone')->nullable();
                $table->text('observacoes')->nullable();
            }

            // Campos para Secretários
            if (!Schema::hasColumn('users', 'setor')) {
                $table->string('setor')->nullable();
                $table->string('ramal')->nullable();
            }

            // Campos para Admin
            if (!Schema::hasColumn('users', 'super_admin')) {
                $table->boolean('super_admin')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'area_atuacao', 'biografia',
                'responsavel_nome', 'responsavel_telefone', 'observacoes',
                'setor', 'ramal', 'super_admin'
            ]);
        });
    }
};