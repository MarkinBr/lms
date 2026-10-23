<?php

use App\Http\Controllers\AcompanhamentoInscricaoLinkController;
use App\Http\Controllers\Aluno\AulaController;
use App\Http\Controllers\Aluno\CalendarioController;
use App\Http\Controllers\Aluno\CertificadosController;
use App\Http\Controllers\Aluno\ComunicacaoController;
use App\Http\Controllers\Aluno\CursosController;
use App\Http\Controllers\Aluno\DashboardController;
use App\Http\Controllers\Aluno\DocumentosController;
use App\Http\Controllers\Aluno\DocumentosInscricaoController;
use App\Http\Controllers\Aluno\FinanceiroController;
use App\Http\Controllers\Aluno\FrequenciaController;
use App\Http\Controllers\Aluno\NotasController;
use App\Http\Controllers\Aluno\PerfilController;
use App\Http\Controllers\Aluno\SecretariaController;
// Controllers do Portal do Aluno
use App\Http\Controllers\Auth\AlunoLoginController;
use App\Http\Controllers\Auth\AdminAccessTokenController;
use App\Http\Controllers\Auth\RedefinirSenhaController;
use App\Http\Controllers\DREController;
use App\Http\Controllers\ExtratoAlunoController;
use App\Http\Controllers\MatriculaReportController;
use App\Http\Controllers\MensalidadeReportController;
use App\Http\Controllers\ReciboController;
use App\Http\Controllers\ReciboPublicoController;
use App\Http\Controllers\RelatorioFinanceiroController;
use App\Http\Controllers\RelatorioFluxoCaixaController;
use App\Http\Controllers\RelatorioInadimplenciaController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SsoController;
// Controller de Autenticação
use App\Http\Controllers\ValidacaoDeclaracaoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// === 1. SITE INSTITUCIONAL ===
Route::get('/', [SiteController::class, 'index'])->name('site.index');
Route::post('/contato', [SiteController::class, 'contato'])
    ->middleware('throttle:10,1')
    ->name('site.contato');
Route::post('/newsletter', [SiteController::class, 'newsletter'])
    ->middleware('throttle:10,1')
    ->name('site.newsletter');

Route::get('/sobre', fn () => redirect('/#sobre'));
Route::get('/cursos-site', fn () => redirect('/#cursos'));
Route::get('/contato-site', fn () => redirect('/#contato'));
Route::get('/acompanhar-inscricao/{application}', [AcompanhamentoInscricaoLinkController::class, 'show'])
    ->middleware(['signed', 'throttle:10,1'])
    ->name('site.acompanhamento-link');

// === 2. AUTENTICAÇÃO E REDIRECIONAMENTOS ===
Route::get('/login', fn () => redirect()->route('aluno.login'))->name('login');

Route::prefix('admin/token')->name('admin.token.')->group(function () {
    Route::get('/', [AdminAccessTokenController::class, 'create'])->middleware('throttle:10,1')->name('create');
    Route::post('/request', [AdminAccessTokenController::class, 'request'])->middleware(['guest', 'throttle:3,10'])->name('request');
    Route::post('/authenticate', [AdminAccessTokenController::class, 'authenticate'])->middleware(['guest', 'throttle:5,10'])->name('authenticate');
});

Route::middleware('guest')->group(function () {
    Route::get('/portal/login', [AlunoLoginController::class, 'showLoginForm'])->name('aluno.login');
    Route::post('/portal/login', [AlunoLoginController::class, 'login'])->middleware('throttle:20,1')->name('aluno.login.submit');
    Route::get('/senha/esqueci', [RedefinirSenhaController::class, 'esqueci'])->name('password.request');
    Route::post('/senha/esqueci', [RedefinirSenhaController::class, 'enviarLink'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/senha/redefinir/{token}', [RedefinirSenhaController::class, 'redefinir'])->name('password.reset');
    Route::post('/senha/redefinir', [RedefinirSenhaController::class, 'atualizar'])->middleware('throttle:10,1')->name('password.update');
});

Route::post('/portal/logout', [AlunoLoginController::class, 'logout'])->middleware(['auth','aluno'])->name('aluno.logout');

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

    // Issue #49: declaração/histórico/comprovante autorizados via MatriculaPolicy
    // dentro do controller (aluno vê a própria; professor vê suas turmas;
    // secretaria/admin veem tudo; inativo é negado pela Policy).
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
Route::middleware(['auth', 'aluno'])->prefix('portal')->name('aluno.')->group(function () {

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

    // Issue #49: interações de aula exigem perfil de aluno ativo.
    // As três rotas legadas abaixo saíram do grupo aberto 'auth' e passaram
    // a compartilhar o middleware 'aluno' (mesmos nomes de rota, sem quebrar
    // os contratos já usados pelo portal).
    Route::middleware(['aluno'])->group(function () {
        Route::post('/aula/{id}/concluir', [AulaController::class, 'concluir'])->name('aula.concluir');
        Route::post('/aula/{id}/progresso', [AulaController::class, 'atualizarProgresso'])->name('aula.progresso');
        Route::post('/aula/{id}/comentar', [AulaController::class, 'comentar'])->name('aula.comentar');
    });

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
    // A entrada principal do portal é a Central de Documentos da Inscrição.
    // A tela legada de declarações continua disponível em /documentos/emitidos.
    Route::get('/documentos', fn () => redirect()->route('aluno.documentos-inscricao'))->name('documentos');
    Route::get('/documentos/emitidos', [DocumentosController::class, 'index'])->name('documentos.emitidos');
    Route::post('/documentos/solicitar-declaracao', [DocumentosController::class, 'solicitarDeclaracao'])->name('documentos.solicitar-declaracao');
    Route::get('/documentos/declaracao/{id}/download', [DocumentosController::class, 'download'])->name('documentos.download');
    Route::get('/documentos/historico/{matricula}', [DocumentosController::class, 'historicoEscolar'])->name('documentos.historico');

    // Issue #53: Central de Documentos da Inscrição (documentos enviados
    // pelo candidato, disco privado `applications`). Não confundir com o
    // bloco acima, que trata documentos emitidos pela instituição.
    Route::get('/documentos-inscricao', [DocumentosInscricaoController::class, 'index'])
        ->middleware('aluno')
        ->name('documentos-inscricao');
    Route::post('/documentos-inscricao', [DocumentosInscricaoController::class, 'store'])
        ->middleware(['aluno', 'throttle:10,1'])
        ->name('documentos-inscricao.store');

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
    Route::get('/configuracoes', fn () => view('aluno.configuracoes.index'))->name('configuracoes');
    Route::get('/forum', fn () => view('aluno.forum.index'))->name('forum');

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
Route::get('/sso/callback', [SsoController::class, 'callback'])
    ->name('sso.callback')
    ->middleware('throttle:10,1'); // Máximo 10 tentativas por minuto

// v9.3.5 (LO-01): Logout SSO Bidirecional (LMS → Live)
Route::post('/sso/logout', [SsoController::class, 'logoutBidirecional'])
    ->name('sso.logout')
    ->middleware(['auth', 'throttle:10,1']);

// === RECIBO PÚBLICO SEGURO (LGPD - Patch 06) ===

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

Route::middleware(['auth'])->group(function () {
    Route::get('/relatorios/inadimplencia/aging',
        [RelatorioInadimplenciaController::class, 'aging'])
        ->name('relatorios.inadimplencia.aging');

    Route::get('/relatorios/fluxo-projetado',
        [RelatorioFluxoCaixaController::class, 'projetado'])
        ->name('relatorios.fluxo.projetado');

    // Issue #49: extrato do aluno exige autenticação + autorização por vínculo
    // (próprio aluno) ou nível de gestão; a verificação fina fica no controller
    // (403 para gestão inativa, 404 para aluno de terceiro — sem vazar dados).
    Route::get('/relatorios/aluno/{userId}/extrato',
        [ExtratoAlunoController::class, 'gerar'])
        ->name('relatorios.aluno.extrato');
});

// === DRE (Auditoria v2026.06) ===

Route::middleware(['auth'])->group(function () {
    Route::get('/relatorios/dre', [DREController::class, 'gerar'])
        ->name('relatorios.dre');
});
