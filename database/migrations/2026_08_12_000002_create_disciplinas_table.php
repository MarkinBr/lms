<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cursos')) {
            return;
        }

        Schema::create('disciplinas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')
                ->constrained('cursos')
                ->restrictOnDelete();
            $table->string('codigo');
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->unsignedInteger('carga_horaria');
            $table->unsignedInteger('ordem');
            $table->boolean('ativa')->default(true);
            $table->timestamps();

            $table->unique(['curso_id', 'codigo'], 'disciplinas_curso_codigo_unique');
            $table->index(['curso_id', 'ativa', 'ordem'], 'disciplinas_curso_ativa_ordem_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disciplinas');
    }
};
