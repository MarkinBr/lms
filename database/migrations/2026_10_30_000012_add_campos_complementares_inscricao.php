<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            // Ausência de informação histórica permanece NULL, inclusive o aceite.
            $table->integer('ano_conclusao')->nullable();
            $table->boolean('formacao_teologica')->nullable();
            $table->string('instituicao_anterior', 255)->nullable();
            $table->enum('como_conheceu', ['indicacao', 'redes_sociais', 'igreja', 'site'])->nullable();
            $table->string('indicacao_nome', 255)->nullable();
            $table->timestamp('aceite_termos_em')->nullable();
            $table->string('termos_versao')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'ano_conclusao',
                'formacao_teologica',
                'instituicao_anterior',
                'como_conheceu',
                'indicacao_nome',
                'aceite_termos_em',
                'termos_versao',
            ]);
        });
    }
};
