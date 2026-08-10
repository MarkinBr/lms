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
        Schema::create('progressos', function (Blueprint $table) {
            $table->id();
            
            // Relacionamentos (Chaves Estrangeiras)
            // Certifique-se de que as tabelas 'users' e 'aulas' já existem
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('aula_id')->constrained('aulas')->onDelete('cascade');
            
            // Dados de Progresso
            $table->integer('progresso_porcentagem')->default(0);
            $table->boolean('concluida')->default(false);
            $table->integer('tempo_estudo_minutos')->default(0);
            $table->text('notas')->nullable();
            
            // Dados de Quiz (Opcional, caso a aula tenha quiz)
            $table->integer('quiz_score')->nullable();
            $table->json('quiz_respostas')->nullable(); // Armazena as respostas em JSON
            $table->json('quiz_tentativas')->nullable(); // Histórico de tentativas
            $table->timestamp('quiz_concluido_em')->nullable();
            
            // Metadados
            $table->timestamp('data_conclusao')->nullable();
            $table->timestamps();
            
            // Garante que um aluno só tenha um registro de progresso por aula
            $table->unique(['user_id', 'aula_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('progressos');
    }
};