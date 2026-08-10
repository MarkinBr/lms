<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Importante para inserir os dados

return new class extends Migration
{
    public function up()
    {
        // 1. Cria a estrutura da tabela
        Schema::create('config', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('nome', 100)->nullable();
            $table->string('email', 50)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('endereco', 100)->nullable();
            $table->string('instagram', 100)->nullable();
            $table->string('logo', 100)->nullable();
            $table->string('icone', 100)->nullable();
            $table->string('logo_rel', 100)->nullable();
            
            // Configurações do sistema
            $table->string('ativo', 5)->nullable();
            $table->decimal('multa_atraso', 8, 2)->nullable();
            $table->decimal('juros_atraso', 8, 2)->nullable();
            $table->string('marca_dagua', 5)->nullable();
            $table->string('assinatura_recibo', 5)->nullable();
            $table->string('impressao_automatica', 5)->nullable();
            $table->string('cnpj', 25)->nullable();
            $table->string('entrar_automatico', 5)->nullable();
            $table->string('mostrar_preloader', 5)->nullable();
            $table->string('ocultar_mobile', 5)->nullable();
            
            // Whatsapp e Integrações
            $table->string('api_whatsapp', 25)->nullable();
            $table->string('token_whatsapp', 70)->nullable();
            $table->string('instancia_whatsapp', 70)->nullable();
            $table->string('alterar_acessos', 5)->nullable();
            $table->string('dados_pagamento', 100)->nullable();
            $table->string('abertura_caixa', 5)->nullable();
            $table->string('access_token', 255)->nullable();
            $table->string('public_key', 255)->nullable();
            
            // Outros campos
            $table->string('logo_painel', 100)->nullable();
            $table->string('imagem_assinatura', 100)->nullable();
            $table->integer('empresa')->nullable();
            $table->string('cidade_escola', 50)->nullable();
            $table->string('responsavel_escola', 50)->nullable();
            $table->string('cargo_responsavel', 50)->nullable();
            $table->string('qrcode_pix', 100)->nullable();
            
            // Dados bancários para carne
            $table->string('nome_banco_carne', 50)->nullable();
            $table->string('tipo_conta_carne', 35)->nullable();
            $table->string('conta_carne', 20)->nullable();
            $table->string('agencia_carne', 20)->nullable();
            $table->string('beneficiario_conta_carne', 50)->nullable();
            $table->string('tipo_chave_pix_carne', 50)->nullable();
            $table->string('chave_pix_carne', 50)->nullable();
            
            // Educacional
            $table->string('inep', 50)->nullable();
            $table->string('censo', 50)->nullable();
            $table->string('ensino_fundamental', 35)->nullable();
            $table->string('media_historico', 35)->nullable();
            $table->string('assinatura_cliente', 5)->nullable();
            
            // O sistema antigo não usava timestamps do Laravel, mas é bom ter
            // $table->timestamps(); 
        });

        // 2. Insere o dado padrão (Seed) que estava no SQL
        DB::table('config')->insert([
            'nome' => 'Centro Educacional, Teologico e Social da IEADP',
            'email' => 'contato@cetsadp.com.br',
            'telefone' => '(21) 99878-4793',
            'endereco' => 'Rua da Assembleia N45 - Centro - Paracambi - RJ',
            'instagram' => 'ieadp_oficial',
            'logo' => 'logo.png',
            'icone' => 'icone.png',
            'logo_rel' => 'logo.jpg',
            'ativo' => 'Sim',
            'multa_atraso' => 0.00,
            'juros_atraso' => 0.00,
            'marca_dagua' => 'Sim',
            'assinatura_recibo' => 'Não',
            'impressao_automatica' => 'Não',
            'cnpj' => '30.191.746/0001-90',
            'entrar_automatico' => 'Sim',
            'mostrar_preloader' => 'Sim',
            'ocultar_mobile' => 'Sim',
            'api_whatsapp' => 'Não',
            'alterar_acessos' => 'Sim',
            'abertura_caixa' => 'Sim',
            'empresa' => 0,
            'responsavel_escola' => 'Diretor Geral',
            'tipo_conta_carne' => 'Corrente',
            'ensino_fundamental' => 'Outros',
            'media_historico' => 'Decimal',
            'assinatura_cliente' => 'Sim'
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('config');
    }
};