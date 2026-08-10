<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Informações da Instituição
    |--------------------------------------------------------------------------
    |
    | Dados institucionais que serão utilizados em todos os documentos,
    | páginas de validação e declarações do sistema.
    |
    */

    'nome_completo' => 'CETSADP',
    'nome_extenso' => 'Centro Educacional, Teológico e Social da Assembleia de Deus de Paracambi',
    'sigla' => 'CETSADP',
    
    /*
    |--------------------------------------------------------------------------
    | Endereço
    |--------------------------------------------------------------------------
    */
    'endereco' => [
        'rua' => 'Rua Afonso Franco',
        'numero' => '15',
        'complemento' => '',
        'bairro' => 'Centro',
        'cidade' => 'Paracambi',
        'estado' => 'RJ',
        'cep' => '26600-000',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Contatos
    |--------------------------------------------------------------------------
    */
    'contatos' => [
        'telefone' => '(21) 1234-5678',
        'celular' => '(21) 98765-4321',
        'email' => 'contato@cetsadp.com.br',
        'website' => 'www.cetsadp.com.br',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Redes Sociais
    |--------------------------------------------------------------------------
    */
    'redes_sociais' => [
        'facebook' => '',
        'instagram' => '',
        'youtube' => '',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Logo
    |--------------------------------------------------------------------------
    */
    'logo' => [
        'path' => 'assets/images/logo.png',
        'alt' => 'Logo CETSADP',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Sistema de Validação
    |--------------------------------------------------------------------------
    */
    'sistema_validacao' => [
        'versao' => '2.0',
        'nome' => 'Sistema de Validação Digital',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Cores Institucionais
    |--------------------------------------------------------------------------
    */
    'cores' => [
        'primaria' => '#003D7A',
        'secundaria' => '#1e40af',
        'sucesso' => '#16a34a',
        'erro' => '#dc2626',
        'alerta' => '#eab308',
    ],
];