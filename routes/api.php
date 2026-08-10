<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\Api\V1\ApplicationController;

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

// ============ API V1 - INSCRIÇÕES DE CANDIDATOS ============
Route::prefix('v1')->group(function () {
    // Rotas públicas (sem autenticação)
    Route::prefix('applications')->group(function () {
        // Candidato cria inscrição
        Route::post('/', [ApplicationController::class, 'store']);
        
        // Candidato consulta sua inscrição por protocolo
        Route::get('/by-protocol/{protocol}', [ApplicationController::class, 'showByProtocol']);
        
        // Candidato envia documento complementar
        Route::post('/{application}/documents', [ApplicationController::class, 'uploadDocument'])
            ->middleware('auth:sanctum');
    });
    
    // Rotas protegidas (Secretaria/Admin)
    Route::middleware(['auth:sanctum', 'role:secretary,admin,super_admin'])->group(function () {
        Route::apiResource('applications', ApplicationController::class)->except(['store']);
        
        // Secretaria analisa inscrição (aprova/rejeita)
        Route::patch('applications/{application}/status', [ApplicationController::class, 'updateStatus'])
            ->name('applications.status');
        
        // Lista todas as inscrições com filtros
        Route::get('applications', [ApplicationController::class, 'index'])
            ->name('applications.index');
    });
});