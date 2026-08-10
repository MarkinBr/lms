<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Multa e Juros (BUG-A08 / MF-17)
    |--------------------------------------------------------------------------
    | Aplicados a mensalidades pagas com atraso.
    | Política padrão CETSADP: 2% multa + 1% ao mês (pro-rata diário).
    */

    'multa_pct'     => (float) env('FINANCEIRO_MULTA_PCT', 0.02),  // 2%
    'juros_mes_pct' => (float) env('FINANCEIRO_JUROS_MES_PCT', 0.01), // 1% a.m.

    /*
    |--------------------------------------------------------------------------
    | Dia padrão de vencimento ao cadastrar nova matrícula
    |--------------------------------------------------------------------------
    */

    'dia_vencimento_padrao' => (int) env('FINANCEIRO_DIA_VENC', 10),

    /*
    |--------------------------------------------------------------------------
    | Lembretes e Cobranças (MF-03 / MF-04)
    |--------------------------------------------------------------------------
    */

    'lembrete_dias_antes' => (int) env('FINANCEIRO_LEMBRETE_DIAS', 3),

    'cobranca_escalonamento' => [1, 7, 15, 30], // dias após vencimento

    /*
    |--------------------------------------------------------------------------
    | Categorias padrão (referenciadas pelo Observer e Helpers)
    |--------------------------------------------------------------------------
    */

    'categoria_mensalidades'  => 'Mensalidades',
    'categoria_multas_juros'  => 'Multas e Juros',
    'categoria_taxas_avulsas' => 'Taxas Avulsas',

    /*
    |--------------------------------------------------------------------------
    | Contas padrão
    |--------------------------------------------------------------------------
    */

    'conta_caixa_padrao'  => 'Caixa Secretaria',
    'conta_banco_padrao'  => 'Mercado Pago',

    /*
    |--------------------------------------------------------------------------
    | Métodos de pagamento aceitos
    |--------------------------------------------------------------------------
    */

    'metodos_pagamento' => [
        'dinheiro'       => '💵 Dinheiro',
        'pix'            => '📱 PIX',
        'cartao_credito' => '💳 Cartão de Crédito',
        'cartao_debito'  => '💳 Cartão de Débito',
        'boleto'         => '📄 Boleto',
        'transferencia'  => '🏦 Transferência',
        'mercadopago'    => '🔵 Mercado Pago',
    ],

    /*
    |--------------------------------------------------------------------------
    | Métodos que vão para a conta "Banco"
    |--------------------------------------------------------------------------
    */

    'metodos_banco' => [
        'pix', 'mercadopago', 'boleto', 'bolbradesco',
        'cartao_credito', 'cartao_debito', 'transferencia',
    ],

    /*
    |--------------------------------------------------------------------------
    | Notificações
    |--------------------------------------------------------------------------
    */

    'enviar_whatsapp_auto'  => (bool) env('FINANCEIRO_AUTO_WHATSAPP', true),
    'enviar_email_auto'     => (bool) env('FINANCEIRO_AUTO_EMAIL', true),
    'enviar_push_auto'      => (bool) env('FINANCEIRO_AUTO_PUSH', true),
];