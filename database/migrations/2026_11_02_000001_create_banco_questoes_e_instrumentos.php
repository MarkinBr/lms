<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banco_questoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disciplina_id')->constrained('disciplinas')->restrictOnDelete();
            $table->foreignId('aula_id')->nullable()->constrained('aulas')->nullOnDelete();
            $table->foreignId('criada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo', 32);
            $table->string('dificuldade', 16);
            $table->text('enunciado');
            $table->json('alternativas')->nullable();
            $table->json('gabarito')->nullable();
            $table->text('explicacao')->nullable();
            $table->string('origem', 32)->default('manual');
            $table->text('referencia')->nullable();
            $table->string('status', 20)->default('rascunho');
            $table->timestamps();
            $table->index(['disciplina_id', 'dificuldade', 'status']);
        });

        Schema::create('instrumentos_avaliacao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disciplina_id')->constrained('disciplinas')->restrictOnDelete();
            $table->foreignId('aula_id')->nullable()->constrained('aulas')->nullOnDelete();
            $table->foreignId('criado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo', 24);
            $table->string('titulo');
            $table->text('instrucoes')->nullable();
            $table->unsignedInteger('tempo_limite_minutos')->nullable();
            $table->unsignedTinyInteger('tentativas_permitidas')->default(1);
            $table->boolean('embaralhar')->default(false);
            $table->string('status', 20)->default('rascunho');
            $table->timestamps();
            $table->index(['disciplina_id', 'tipo', 'status']);
        });

        Schema::create('instrumento_questoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instrumento_id')->constrained('instrumentos_avaliacao')->cascadeOnDelete();
            $table->foreignId('questao_id')->constrained('banco_questoes')->restrictOnDelete();
            $table->unsignedInteger('ordem');
            $table->unsignedInteger('pontos')->default(1);
            $table->unique(['instrumento_id', 'questao_id']);
            $table->unique(['instrumento_id', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instrumento_questoes');
        Schema::dropIfExists('instrumentos_avaliacao');
        Schema::dropIfExists('banco_questoes');
    }
};
