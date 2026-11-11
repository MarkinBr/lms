<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acervo_didaticos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('curso_id')->nullable()->constrained('cursos')->nullOnDelete();
            $table->foreignId('disciplina_id')->nullable()->constrained('disciplinas')->nullOnDelete();
            $table->foreignId('aula_id')->nullable()->constrained('aulas')->nullOnDelete();
            $table->string('titulo');
            $table->string('tipo', 20);
            $table->text('url');
            $table->string('categoria', 180)->nullable();
            $table->string('classificacao', 30)->default('pendente');
            $table->string('chave_importacao', 64)->unique();
            $table->boolean('publicado')->default(false);
            $table->timestamps();
            $table->index(['curso_id', 'publicado']);
            $table->index(['disciplina_id', 'publicado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acervo_didaticos');
    }
};
