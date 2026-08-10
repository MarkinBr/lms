<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\ReciboController;
use App\Http\Controllers\MensalidadeReportController;
use App\Http\Controllers\RelatorioFinanceiroController;
use App\Http\Controllers\MatriculaReportController;
use App\Http\Controllers\ValidacaoDeclaracaoController;

// Controllers do Portal do Aluno
use App\Http\Controllers\Aluno\DashboardController;
use App\Http\Controllers\Aluno\CursosController;
use App\Http\Controllers\Aluno\FinanceiroController;
use App\Http\Controllers\Aluno\CalendarioController;
use App\Http\Controllers\Aluno\NotasController;
use App\Http\Controllers\Aluno\FrequenciaController;
use App\Http\Controllers\Aluno\ComunicacaoController;
use App\Http\Controllers\Aluno\CertificadosController;
use App\Http\Controllers\Aluno\DocumentosController;
use App\Http\Controllers\Aluno\PerfilController;
use App\Http\Controllers\Aluno\SecretariaController;
use App\Http\Controllers\Aluno\AulaController;

// Controller de Autenticação
use App\Http\Controllers\Auth\AlunoLoginController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// === 1. SITE INSTITUCIONAL ===
Route::get('/', [SiteController::class, 'index'])->name('site.index');
Route::post('/contato', [SiteController::class, 'contato'])->name('site.contato');
Route::post('/newsletter', [SiteController::class, 'newsletter'])->name('site.newsletter');

Route::get('/sobre', fn() => redirect('/#sobre'));
Route::get('/cursos-site', fn() => redirect('/#cursos'));
Route::get('/contato-site', fn() => redirect('/#contato'));


// === 2. AUTENTICAÇÃO E REDIRECIONAMENTOS ===
Route::get('/login', fn() => redirect()->route('aluno.login'))->name('login');

Route::middleware('guest')->group(function () {
    Route::get('/portal/login', [AlunoLoginController::class, 'showLoginForm'])->name('aluno.login');
    Route::post('/portal/login', [AlunoLoginController::class, 'login'])->name('aluno.login.submit');
});

Route::post('/portal/logout', [AlunoLoginController::class, 'logout'])->name('aluno.logout');

Route::post('/aluno/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/admin/login');
})->name('filament.aluno.auth.logout');


// === 3. RELATÓRIOS E DOCUMENTOS (ÁREA RESTRITA) ===
Route::middleware(['auth'])->group(function () {
    Route::get('/mensalidade/{id}/recibo', [MensalidadeReportController::class, 'gerarRecibo'])->name('mensalidade.recibo.pdf');
    Route::get('/financeiro/{id}/recibo-termico', [FinanceiroController::class, 'reciboTermico'])->name('financeiro.recibo.termico');
    
    Route::get('/mensalidades/relatorio/geral', [MensalidadeReportController::class, 'relatorioGeral'])->name('mensalidades.relatorio.geral.pdf');
    Route::get('/mensalidades/relatorio/periodo', [MensalidadeReportController::class, 'relatorioPeriodo'])->name('mensalidades.relatorio.periodo.pdf');
    Route::get('/mensalidades/relatorio/lote', [MensalidadeReportController::class, 'relatorioLote'])->name('mensalidades.relatorio.pdf'); 

    Route::get('/financeiro/relatorio/mensal', [RelatorioFinanceiroController::class, 'relatorioMensal'])->name('financeiro.relatorio.mensal.pdf');
    Route::get('/financeiro/relatorio/anual', [RelatorioFinanceiroController::class, 'relatorioAnual'])->name('financeiro.relatorio.anual.pdf');

    Route::get('/matricula/{id}/declaracao', [MatriculaReportController::class, 'gerarDeclaracao'])->name('matricula.declaracao.pdf');
    Route::get('/matricula/{id}/historico', [MatriculaReportController::class, 'gerarHistorico'])->name('matricula.historico.pdf');
    Route::get('/matricula/{id}/comprovante', [MatriculaReportController::class, 'gerarComprovante'])->name('matricula.comprovante.pdf');
    
    Route::get('/documentos/declaracao/{id}/visualizar', [DocumentosController::class, 'visualizar'])->name('documentos.visualizar');
    Route::get('/documentos/historico/{matriculaId}/visualizar', [DocumentosController::class, 'visualizarHistorico'])->name('documentos.historico.visualizar');
});


// === 4. FUNCIONALIDADES PÚBLICAS ===
Route::get('/recibo/{id}', [ReciboController::class, 'show'])->name('recibo.show');

Route::prefix('validar')->group(function () {
    Route::get('/', [ValidacaoDeclaracaoController::class, 'formulario'])->name('validacao.formulario');
    Route::post('/buscar', [ValidacaoDeclaracaoController::class, 'buscar'])->name('validacao.buscar');
    Route::get('/{codigo}', [ValidacaoDeclaracaoController::class, 'validar'])->name('validacao.validar');
});



// === 5. PORTAL DO ALUNO ===
Route::middleware(['auth'])->prefix('portal')->name('aluno.')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Financeiro
    Route::get('/financeiro', [FinanceiroController::class, 'index'])->name('financeiro');
    Route::post('/financeiro/{id}/pix', [FinanceiroController::class, 'gerarPix'])->name('financeiro.pix');
    Route::post('/financeiro/{id}/boleto', [FinanceiroController::class, 'gerarBoleto'])->name('financeiro.boleto');
    
    // Acadêmico - Cursos e Aulas
    Route::get('/cursos', [CursosController::class, 'index'])->name('cursos');
    Route::get('/curso/{id}', [CursosController::class, 'show'])->name('curso.show');
    Route::get('/aula/{id}', [AulaController::class, 'show'])->name('aula');
    
    // Interação de Aula
    Route::post('/aula/{id}/concluir', [CursosController::class, 'marcarConcluida'])->name('aula.concluir');
    Route::post('/aula/{id}/progresso', [CursosController::class, 'atualizarProgresso'])->name('aula.progresso');
    Route::post('/aula/{id}/comentar', [CursosController::class, 'comentar'])->name('aula.comentar');
    
    // Calendário
    Route::get('/calendario', [CalendarioController::class, 'index'])->name('calendario');
    Route::get('/calendario/proximos-eventos', [CalendarioController::class, 'proximosEventos'])->name('calendario.proximos');
    
    // Notas e Frequência
    Route::get('/notas', [NotasController::class, 'index'])->name('notas');
    Route::get('/notas/curso/{id}', [NotasController::class, 'curso'])->name('notas.curso');
    Route::get('/frequencia', [FrequenciaController::class, 'index'])->name('frequencia');
    Route::get('/frequencia/curso/{id}', [FrequenciaController::class, 'curso'])->name('frequencia.curso');
    
    // Comunicação
    Route::get('/comunicacao', [ComunicacaoController::class, 'index'])->name('comunicacao');
    Route::get('/comunicacao/{id}', [ComunicacaoController::class, 'show'])->name('comunicacao.show');
    Route::get('/comunicacao/mensagens', [ComunicacaoController::class, 'mensagens'])->name('comunicacao.mensagens');
    Route::post('/comunicacao/marcar-lido', [ComunicacaoController::class, 'marcarComoLido'])->name('comunicacao.marcar-lido');
    
    // Certificados
    Route::get('/certificados', [CertificadosController::class, 'index'])->name('certificados');
    Route::get('/certificados/{id}/download', [CertificadosController::class, 'download'])->name('certificados.download');
    Route::post('/certificados/solicitar/{matricula}', [CertificadosController::class, 'solicitar'])->name('certificados.solicitar');
    
    // Documentos
    Route::get('/documentos', [DocumentosController::class, 'index'])->name('documentos');
    Route::post('/documentos/solicitar-declaracao', [DocumentosController::class, 'solicitarDeclaracao'])->name('documentos.solicitar-declaracao');
    Route::get('/documentos/declaracao/{id}/download', [DocumentosController::class, 'download'])->name('documentos.download');
    Route::get('/documentos/historico/{matricula}', [DocumentosController::class, 'historicoEscolar'])->name('documentos.historico');
    
    // Perfil
    Route::get('/perfil', [PerfilController::class, 'index'])->name('perfil');
    Route::put('/perfil', [PerfilController::class, 'update'])->name('perfil.update');
    Route::post('/perfil/alterar-senha', [PerfilController::class, 'alterarSenha'])->name('perfil.alterar-senha');
    Route::delete('/perfil/foto', [PerfilController::class, 'deletarFoto'])->name('perfil.deletar-foto');
    Route::get('/perfil/exportar-dados', [PerfilController::class, 'exportarDados'])->name('perfil.exportar-dados');
    Route::post('/perfil/solicitar-exclusao', [PerfilController::class, 'solicitarExclusao'])->name('perfil.solicitar-exclusao');
    
    // Secretaria (Requerimentos)
    Route::get('/secretaria', [SecretariaController::class, 'index'])->name('secretaria');
    Route::get('/secretaria/criar', [SecretariaController::class, 'criar'])->name('secretaria.criar');
    Route::post('/secretaria', [SecretariaController::class, 'store'])->name('secretaria.store');
    Route::get('/secretaria/{id}', [SecretariaController::class, 'show'])->name('secretaria.show');
    Route::delete('/secretaria/{id}/cancelar', [SecretariaController::class, 'cancelar'])->name('secretaria.cancelar');
    
    // Configurações, Fórum
    Route::get('/configuracoes', fn() => view('aluno.configuracoes.index'))->name('configuracoes');
    Route::get('/forum', fn() => view('aluno.forum.index'))->name('forum');
    
    // Sub-grupo Aluno
    Route::middleware(['auth', 'aluno'])->group(function () {
        Route::get('/aula-v2/{aula}', [AulaController::class, 'show'])->name('aula.show.nova'); 
        Route::post('/aula-v2/{aula}/concluir', [AulaController::class, 'concluir'])->name('aula.concluir.nova');
        Route::post('/aula-v2/{aula}/progresso', [AulaController::class, 'atualizarProgresso'])->name('aula.progresso.nova');
        Route::post('/aula-v2/{aula}/tempo', [AulaController::class, 'registrarTempo'])->name('aula.tempo');
        Route::post('/aula-v2/{aula}/quiz', [AulaController::class, 'salvarQuiz'])->name('aula.quiz');
        Route::post('/aula-v2/{aula}/notas', [AulaController::class, 'salvarNotas'])->name('aula.notas');
    });

});
// === SSO INTEGRATION (Etapa 4) ===
Route::get('/sso/callback', [App\Http\Controllers\SsoController::class, 'callback'])
    ->name('sso.callback')
    ->middleware('throttle:10,1'); // Máximo 10 tentativas por minuto

// v9.3.5 (LO-01): Logout SSO Bidirecional (LMS → Live)
Route::post('/sso/logout', [App\Http\Controllers\SsoController::class, 'logoutBidirecional'])
    ->name('sso.logout')
    ->middleware(['auth', 'throttle:10,1']);

// === RECIBO PÚBLICO SEGURO (LGPD - Patch 06) ===
use App\Http\Controllers\ReciboPublicoController;

Route::prefix('recibo/v')->name('recibo.publico.')->group(function () {
    Route::get('/{codigo}', [ReciboPublicoController::class, 'form'])
        ->where('codigo', '[0-9a-f-]{36}')
        ->middleware('throttle:30,1')
        ->name('form');
    Route::post('/{codigo}', [ReciboPublicoController::class, 'show'])
        ->where('codigo', '[0-9a-f-]{36}')
        ->middleware('throttle:10,1')
        ->name('show');
});

// === RELATÓRIOS AVANÇADOS (Auditoria v2026.06) ===
use App\Http\Controllers\RelatorioInadimplenciaController;
use App\Http\Controllers\RelatorioFluxoCaixaController;
use App\Http\Controllers\ExtratoAlunoController;

Route::middleware(['auth'])->group(function () {
    Route::get('/relatorios/inadimplencia/aging',
        [RelatorioInadimplenciaController::class, 'aging'])
        ->name('relatorios.inadimplencia.aging');

    Route::get('/relatorios/fluxo-projetado',
        [RelatorioFluxoCaixaController::class, 'projetado'])
        ->name('relatorios.fluxo.projetado');

    Route::get('/relatorios/aluno/{userId}/extrato',
        [ExtratoAlunoController::class, 'gerar'])
        ->name('relatorios.aluno.extrato');
});

// === DRE (Auditoria v2026.06) ===
use App\Http\Controllers\DREController;

Route::middleware(['auth'])->group(function () {
    Route::get('/relatorios/dre', [DREController::class, 'gerar'])
        ->name('relatorios.dre');
});
