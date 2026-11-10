<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Mercado Pago
    |--------------------------------------------------------------------------
    | O Service busca por 'token', então mapeamos sua variável do .env para cá.
    */
    'mercadopago' => [
        'token' => env('MP_ACCESS_TOKEN'),      // Essencial para o backend
        'public_key' => env('MP_PUBLIC_KEY'),   // Útil para frontend se precisar
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp API
    |--------------------------------------------------------------------------
    | Configuração necessária para o WhatsAppService.php funcionar.
    */
    'whatsapp' => [
        'api_url' => env('WHATSAPP_API_URL'),
        'token' => env('WHATSAPP_API_TOKEN'),
        'instance_id' => env('WHATSAPP_INSTANCE_ID'),
    ],

    // CETSADP SSO Integration (Etapa 4)
    'cetsadp' => [
        'live_api_url' => env('CETSADP_LIVE_API_URL', 'http://backend:3000'),
        'sso_secret' => env('CETSADP_SSO_SECRET', ''),
    ],
];