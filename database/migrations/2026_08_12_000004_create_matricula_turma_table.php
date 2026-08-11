<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('matriculas') || ! Schema::hasTable('turmas')) {
            return;
        }

        Schema::create('matricula_turma', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matricula_id')
                ->constrained('matriculas')
                ->cascadeOnDelete();
            $table->foreignId('turma_id')
                ->constrained('turmas')
                ->cascadeOnDelete();
            $table->string('status')->default('ativo');
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->unique(['matricula_id', 'turma_id'], 'matricula_turma_unique');
            $table->index(['turma_id', 'status'], 'matricula_turma_turma_status_index');
            $table->index(['matricula_id', 'status'], 'matricula_turma_matricula_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matricula_turma');
    }
};
