<?php

use App\Http\Controllers\Api\V1\ApplicationController;
use App\Http\Controllers\Api\V1\ApplicationDocumentDownloadController;
use App\Http\Controllers\Api\V1\CourseCatalogController;
use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
    Route::get('/cursos', [CourseCatalogController::class, 'index']);

    // Rotas públicas (sem autenticação)
    Route::prefix('applications')->group(function () {
        // Candidato cria inscrição única
        Route::post('/', [ApplicationController::class, 'store']);

        // Candidato cria inscrições em lote (1 a 3 cursos por envio)
        Route::post('/batch', [ApplicationController::class, 'storeBatch']);

        // Candidato acompanha a inscrição com protocolo + e-mail
        // (substitui a antiga consulta pública por protocolo, que expunha todos os dados)
        Route::post('/acompanhamento', [ApplicationController::class, 'acompanhar'])
            ->middleware('throttle:10,1');

        // Candidato envia documento complementar
        Route::post('/{application}/documents', [ApplicationController::class, 'uploadDocument'])
            ->middleware('auth:sanctum');
    });

    // Rotas protegidas (Secretaria/Admin)
    Route::middleware(['auth:sanctum', 'role:secretario,admin'])->group(function () {
        Route::get('applications', [ApplicationController::class, 'index'])
            ->name('applications.index');

        Route::get('applications/{application}', [ApplicationController::class, 'show'])
            ->name('applications.show');

        Route::delete('applications/{application}', [ApplicationController::class, 'destroy'])
            ->name('applications.destroy');

        // Secretaria analisa inscrição pelo fluxo canônico de aprovação/rejeição
        Route::patch('applications/{application}/status', [ApplicationController::class, 'updateStatus'])
            ->name('applications.status');
    });

    // === Download protegido de documentos de inscrição (Issue #50) ===
    // Sanctum + ApplicationDocumentPolicy (autodiscovery — não existe
    // AuthServiceProvider). Fica fora do grupo role:secretario,admin porque o
    // candidato-dono também baixa os próprios documentos.
    Route::middleware('auth:sanctum')->get(
        'applications/{application}/documents/{document}',
        ApplicationDocumentDownloadController::class,
    )->name('applications.documents.download');
});


// Bot Telegram administrativo (Issue #82): webhook público protegido por secret_token.
\Illuminate\Support\Facades\Route::post('/telegram/cetsadp/webhook',
    \App\Http\Controllers\TelegramWebhookController::class
)->middleware('throttle:60,1')->name('telegram.admin.webhook');
