<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aulas', function (Blueprint $table) {
            // Campos de áudio
            $table->string('audio_url')->nullable()->after('video_url');
            $table->string('audio_titulo')->nullable()->after('audio_url');

            // Campos de PDF/Documento
            $table->string('pdf_url')->nullable()->after('audio_titulo');
            $table->string('pdf_titulo')->nullable()->after('pdf_url');

            // Thumbnail personalizado
            $table->string('thumbnail')->nullable()->after('pdf_titulo');

            // Blocos de conteúdo estruturado (JSON) - permite múltiplos tipos na mesma aula
            $table->json('blocos_conteudo')->nullable()->after('conteudo_texto');

            // Sistema de questões avançado
            $table->json('questoes')->nullable()->after('blocos_conteudo');
            $table->integer('questoes_quantidade')->default(0)->after('questoes');
            $table->boolean('questoes_aleatorias')->default(false)->after('questoes_quantidade');
            $table->integer('nota_minima_aprovacao')->nullable()->after('questoes_aleatorias');

            // Anexos múltiplos (PDFs, docs, slides, etc.)
            $table->json('anexos')->nullable()->after('materiais');

            // Configurações extras
            $table->boolean('permite_download')->default(true)->after('liberada');
            $table->boolean('permite_comentarios')->default(true)->after('permite_download');
            $table->integer('tempo_minimo_minutos')->nullable()->after('permite_comentarios');
            $table->string('video_provider')->nullable()->after('video_url'); // youtube, vimeo, bunny, direto
        });
    }

    public function down(): void
    {
        Schema::table('aulas', function (Blueprint $table) {
            $table->dropColumn([
                'audio_url', 'audio_titulo',
                'pdf_url', 'pdf_titulo',
                'thumbnail',
                'blocos_conteudo', 'questoes', 'questoes_quantidade',
                'questoes_aleatorias', 'nota_minima_aprovacao',
                'anexos', 'permite_download', 'permite_comentarios',
                'tempo_minimo_minutos', 'video_provider',
            ]);
        });
    }
};
