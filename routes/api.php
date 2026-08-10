<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// ============ WEBHOOKS MERCADO PAGO ============
// Importante: Essas rotas devem estar SEM autenticação para o MP conseguir acessar

Route::prefix('webhook')->group(function () {
    // Webhook principal do Mercado Pago
    Route::post('/mercadopago', [WebhookController::class, 'mercadoPago'])
        ->name('webhook.mercadopago');
    
    // Rota de teste
    Route::any('/test', [WebhookController::class, 'test'])
        ->name('webhook.test');
});