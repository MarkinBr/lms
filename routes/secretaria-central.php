<?php

use App\Http\Controllers\Secretaria\CentralAcessosController;
use App\Http\Middleware\AtendimentoSecretaria;
use Illuminate\Support\Facades\Route;

Route::middleware([AtendimentoSecretaria::class])->prefix('secretaria/central')->group(function () {
    Route::get('/', [CentralAcessosController::class, 'home'])->name('secretaria.central');
    Route::prefix('acessos')->name('secretaria.acessos.')->group(function () {
        Route::get('/', [CentralAcessosController::class, 'index'])->name('index');
        Route::get('/novo', [CentralAcessosController::class, 'create'])->name('create');
        Route::post('/', [CentralAcessosController::class, 'store'])->middleware('throttle:15,1')->name('store');
        Route::get('/{aluno}/editar', [CentralAcessosController::class, 'edit'])->whereNumber('aluno')->name('edit');
        Route::get('/{aluno}/foto', [CentralAcessosController::class, 'verFoto'])->whereNumber('aluno')->name('foto.ver');
        Route::get('/{aluno}', [CentralAcessosController::class, 'show'])->whereNumber('aluno')->name('show');
        Route::put('/{aluno}', [CentralAcessosController::class, 'update'])->middleware('throttle:20,1')->name('update');
        Route::post('/{aluno}/foto', [CentralAcessosController::class, 'foto'])->middleware('throttle:10,1')->name('foto');
        Route::post('/{aluno}/acao', [CentralAcessosController::class, 'acao'])->middleware('throttle:12,1')->name('acao');
        Route::post('/{aluno}/mensagem', [CentralAcessosController::class, 'mensagem'])->middleware('throttle:12,1')->name('mensagem');
        Route::post('/{aluno}/email', [CentralAcessosController::class, 'email'])->middleware('throttle:5,1')->name('email');
        Route::get('/{aluno}/entrega/{entrega}', [CentralAcessosController::class, 'entrega'])->name('entrega');
        Route::post('/{aluno}/entrega/{entrega}', [CentralAcessosController::class, 'enviar'])->middleware('throttle:10,1')->name('entrega.enviar');
    });
});
Route::match(['GET', 'POST'], '/acesso/confirmar-email/{aluno}', [CentralAcessosController::class, 'confirmarEmail'])
    ->middleware(['signed', 'throttle:10,1'])->whereNumber('aluno')->name('secretaria.acessos.email.confirmar');
