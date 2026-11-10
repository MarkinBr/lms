<?php

use App\Http\Controllers\Aluno\FotoAlunoController;
use App\Http\Controllers\Auth\AcessoAssistidoController;
use Illuminate\Support\Facades\Route;

Route::get('/portal/perfil/foto-privada/{aluno}', FotoAlunoController::class)
    ->middleware(['auth', 'aluno'])->whereNumber('aluno')->name('aluno.foto');

Route::prefix('aluno/acesso-assistido')->name('aluno.assistido.')->group(function () {
    Route::get('/', [AcessoAssistidoController::class, 'formulario'])->name('formulario');
    Route::post('/', [AcessoAssistidoController::class, 'entrar'])->middleware('throttle:10,1')->name('entrar');
    Route::get('/senha', [AcessoAssistidoController::class, 'senha'])->name('senha');
    Route::post('/senha', [AcessoAssistidoController::class, 'definir'])->middleware('throttle:10,1')->name('definir');
    Route::get('/link/{id}', [AcessoAssistidoController::class, 'link'])->whereUuid('id')->name('link');
    Route::post('/link/{id}', [AcessoAssistidoController::class, 'comprovar'])->whereUuid('id')->middleware('throttle:10,1')->name('comprovar');
});
