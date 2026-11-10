<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cadernos_aula', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aluno_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('aula_id')->constrained('aulas')->cascadeOnDelete();
            $table->unsignedInteger('revisao')->default(0);
            $table->json('conteudo');
            $table->timestamps();
            $table->unique(['aluno_id', 'aula_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cadernos_aula');
    }
};
