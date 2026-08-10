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
    Schema::table('users', function (Blueprint $table) {
        $table->string('cpf')->nullable()->unique();
        $table->string('telefone')->nullable();
        $table->string('foto')->nullable();
        $table->string('nivel')->default('Professor'); // Para manter compatibilidade
        $table->boolean('ativo')->default(true);

        // Dados Bancários (Pix)
        $table->string('tipo_chave')->nullable();
        $table->string('chave_pix')->nullable();

        // Endereço (ViaCEP)
        $table->string('cep')->nullable();
        $table->string('endereco')->nullable();
        $table->string('numero')->nullable();
        $table->string('bairro')->nullable();
        $table->string('cidade')->nullable();
        $table->string('estado')->nullable();

        $table->date('data_nasc')->nullable();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
