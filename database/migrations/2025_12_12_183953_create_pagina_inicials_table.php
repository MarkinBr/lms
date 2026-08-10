<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagina_inicials', function (Blueprint $table) {
            $table->id();
            
            // === CONTROLE DE VERSÃO ===
            $table->string('titulo_identificacao')->nullable();
            $table->boolean('ativa')->default(false);

            // === HERO (TOPO) ===
            $table->string('hero_titulo_principal')->nullable();
            $table->string('hero_destaque_1')->nullable();
            $table->string('hero_destaque_2')->nullable();
            $table->text('hero_subtitulo')->nullable();
            $table->string('hero_imagem')->nullable();
            $table->string('hero_video_url')->nullable(); // NOVO: Vídeo de fundo
            $table->string('hero_botao_texto')->nullable();
            $table->string('hero_botao_link')->nullable();
            $table->string('hero_botao_secundario_texto')->nullable(); // NOVO
            $table->string('hero_botao_secundario_link')->nullable(); // NOVO

            // === ESTATÍSTICAS/CONTADORES ===
            $table->json('estatisticas')->nullable(); // NOVO: Números que impressionam

            // === SOBRE NÓS ===
            $table->string('sobre_titulo')->nullable();
            $table->text('sobre_texto')->nullable();
            $table->string('sobre_imagem')->nullable();
            $table->string('sobre_video_url')->nullable(); // NOVO: Vídeo institucional
            $table->json('sobre_diferenciais')->nullable();
            
            // === MISSÃO, VISÃO, VALORES ===
            $table->text('missao')->nullable(); // NOVO
            $table->text('visao')->nullable(); // NOVO
            $table->text('valores')->nullable(); // NOVO

            // === CURSOS ===
            $table->string('cursos_titulo')->nullable();
            $table->text('cursos_descricao')->nullable();
            $table->json('cursos_lista')->nullable();
            $table->json('cursos_categorias')->nullable(); // NOVO: Filtros por categoria

            // === METODOLOGIA/COMO FUNCIONA ===
            $table->string('metodologia_titulo')->nullable(); // NOVO
            $table->text('metodologia_descricao')->nullable(); // NOVO
            $table->json('metodologia_passos')->nullable(); // NOVO: Passo a passo

            // === DEPOIMENTOS ===
            $table->string('depoimentos_titulo')->nullable(); // NOVO
            $table->text('depoimentos_descricao')->nullable(); // NOVO
            $table->json('depoimentos_lista')->nullable(); // NOVO

            // === GALERIA ===
            $table->string('galeria_titulo')->nullable(); // NOVO
            $table->json('galeria_imagens')->nullable(); // NOVO
            $table->json('galeria_videos')->nullable(); // NOVO

            // === BLOG/NOTÍCIAS ===
            $table->string('blog_titulo')->nullable(); // NOVO
            $table->boolean('blog_ativo')->default(true); // NOVO
            $table->json('blog_destaques')->nullable(); // NOVO: Posts em destaque

            // === PARCEIROS ===
            $table->string('parceiros_titulo')->nullable(); // NOVO
            $table->json('parceiros_lista')->nullable(); // NOVO: Logos de parceiros

            // === NEWSLETTER ===
            $table->string('newsletter_titulo')->nullable(); // NOVO
            $table->text('newsletter_descricao')->nullable(); // NOVO
            $table->boolean('newsletter_ativo')->default(true); // NOVO

            // === VANTAGENS ===
            $table->string('vantagens_titulo')->nullable();
            $table->text('vantagens_descricao')->nullable();
            $table->json('vantagens_lista')->nullable();

            // === FAQ ===
            $table->string('faq_titulo')->nullable();
            $table->text('faq_descricao')->nullable(); // NOVO
            $table->json('faq_lista')->nullable();

            // === CTA (Call to Action) FLUTUANTE ===
            $table->boolean('cta_flutuante_ativo')->default(true); // NOVO
            $table->string('cta_flutuante_texto')->nullable(); // NOVO
            $table->string('cta_flutuante_botao')->nullable(); // NOVO
            $table->string('cta_flutuante_link')->nullable(); // NOVO

            // === CONTATO ===
            $table->string('contato_titulo')->nullable();
            $table->text('contato_descricao')->nullable();
            $table->string('endereco')->nullable();
            $table->string('telefone')->nullable();
            $table->string('whatsapp')->nullable(); // NOVO
            $table->string('email')->nullable();
            $table->string('horario_funcionamento')->nullable(); // NOVO
            $table->text('mapa_embedcode')->nullable(); // NOVO: Google Maps
            
            // === REDES SOCIAIS ===
            $table->string('link_facebook')->nullable();
            $table->string('link_instagram')->nullable();
            $table->string('link_youtube')->nullable();
            $table->string('link_linkedin')->nullable(); // NOVO
            $table->string('link_twitter')->nullable(); // NOVO
            $table->string('link_tiktok')->nullable(); // NOVO

            // === SEO ===
            $table->string('seo_titulo')->nullable(); // NOVO
            $table->text('seo_descricao')->nullable(); // NOVO
            $table->string('seo_palavras_chave')->nullable(); // NOVO
            $table->string('seo_imagem')->nullable(); // NOVO: Open Graph

            // === CONFIGURAÇÕES AVANÇADAS ===
            $table->json('cores_personalizadas')->nullable(); // NOVO: Paleta customizável
            $table->string('logo')->nullable(); // NOVO
            $table->string('favicon')->nullable(); // NOVO
            $table->text('codigo_analytics')->nullable(); // NOVO: Google Analytics
            $table->text('codigo_pixel')->nullable(); // NOVO: Facebook Pixel
            $table->boolean('modo_manutencao')->default(false); // NOVO

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagina_inicials');
    }
};