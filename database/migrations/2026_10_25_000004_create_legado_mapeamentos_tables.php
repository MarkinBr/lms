<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legado_mapeamentos', function (Blueprint $table) {
            $table->id();
            $table->string('origem', 80);
            $table->string('tabela', 80);
            $table->string('id_origem', 120);
            $table->string('tabela_destino', 80);
            $table->unsignedBigInteger('id_destino');
            $table->char('impressao_origem', 64);
            $table->char('impressao_destino', 64);
            $table->json('campos');
            $table->timestamps();
            $table->unique(['origem', 'tabela', 'id_origem'], 'legado_identidade_unique');
        });
        Schema::create('legado_materiais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained('cursos')->restrictOnDelete();
            $table->foreignId('aula_id')->nullable()->constrained('aulas')->nullOnDelete();
            $table->string('nome');
            $table->text('url');
            $table->string('tipo', 40)->nullable();
            $table->timestamps();
        });
        Schema::create('legado_registros', function (Blueprint $table) {
            $table->id();
            $table->string('origem', 80);
            $table->string('tabela', 80);
            $table->string('id_origem', 120);
            $table->json('conteudo');
            $table->timestamps();
            $table->unique(['origem', 'tabela', 'id_origem'], 'legado_historico_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legado_registros');
        Schema::dropIfExists('legado_materiais');
        Schema::dropIfExists('legado_mapeamentos');
    }
};
