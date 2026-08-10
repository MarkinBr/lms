<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('declaracoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matricula_id')->constrained('matriculas')->onDelete('cascade');
            $table->string('codigo_validacao', 32)->unique();
            $table->string('tipo_documento')->default('declaracao_matricula');
            $table->timestamp('data_emissao');
            $table->integer('quantidade_visualizacoes')->default(0);
            $table->timestamp('ultima_visualizacao')->nullable();
            $table->string('hash_documento')->nullable(); // Para verificar integridade
            $table->timestamps();
            
            $table->index('codigo_validacao');
            $table->index('matricula_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('declaracoes');
    }
};