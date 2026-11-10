<?php

use App\Http\Controllers\Secretaria\CentralAcessosController;
use App\Http\Controllers\Secretaria\CentralDocumentosController;
use App\Http\Controllers\Secretaria\CentralEnsinoController;
use App\Http\Controllers\Secretaria\CentralFinanceiroController;
use App\Http\Controllers\Secretaria\CentralMatriculasController;
use App\Http\Controllers\Secretaria\EnsinoAvaliacoesController;
use App\Http\Controllers\Secretaria\EnsinoConteudoController;
use App\Http\Controllers\Secretaria\SecretariaAssinaturasController;
use App\Http\Middleware\AtendimentoSecretaria;
use Illuminate\Support\Facades\Route;

Route::middleware([AtendimentoSecretaria::class])->prefix('secretaria/central')->group(function () {
    Route::get('/', [CentralAcessosController::class, 'home'])->name('secretaria.central');
    Route::prefix('acessos')->name('secretaria.acessos.')->group(function () {
        Route::get('/', [CentralAcessosController::class, 'index'])->name('index');
        Route::get('/novo', [CentralAcessosController::class, 'create'])->name('create');
        Route::post('/', [CentralAcessosController::class, 'store'])->middleware('throttle:15,1')->name('store');
        Route::get('/{aluno}/formas-de-acesso', [CentralAcessosController::class, 'formasDeAcesso'])
            ->middleware('throttle:30,1')->whereNumber('aluno')->name('formas');
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
    Route::prefix('ensino')->name('secretaria.ensino.')->group(function () {
        Route::get('/', [CentralEnsinoController::class, 'painel'])->name('painel');
        Route::get('/modulos', [CentralEnsinoController::class, 'modulos'])->name('modulos');
        Route::post('/modulos', [EnsinoConteudoController::class, 'salvarModulo'])->middleware('throttle:15,1')->name('modulos.salvar');
        Route::get('/disciplinas', [CentralEnsinoController::class, 'disciplinas'])->name('disciplinas');
        Route::get('/aulas', [CentralEnsinoController::class, 'aulas'])->name('aulas');
        Route::get('/aulas/nova', [CentralEnsinoController::class, 'aula'])->name('aulas.nova');
        Route::post('/aulas', [EnsinoConteudoController::class, 'salvarAula'])->middleware('throttle:15,1')->name('aulas.salvar');
        Route::get('/aulas/{aula}', [CentralEnsinoController::class, 'aula'])->whereNumber('aula')->name('aula');
        Route::put('/aulas/{aula}', [EnsinoConteudoController::class, 'salvarAula'])->whereNumber('aula')->middleware('throttle:15,1')->name('aulas.atualizar');
        Route::get('/aulas/{aula}/materiais/{indice}', [EnsinoConteudoController::class, 'material'])
            ->whereNumber('aula')->whereNumber('indice')->name('aulas.material');
        Route::get('/notas', [CentralEnsinoController::class, 'notas'])->name('notas');
        Route::get('/frequencia-presencial', [CentralEnsinoController::class, 'frequencias'])->name('frequencias');
        Route::get('/frequencia-ead', [CentralEnsinoController::class, 'ead'])->name('ead');
        Route::get('/avaliacoes', [CentralEnsinoController::class, 'avaliacoes'])->name('avaliacoes');
        Route::post('/avaliacoes', [EnsinoAvaliacoesController::class, 'salvarInstrumento'])->middleware('throttle:15,1')->name('avaliacoes.salvar');
        Route::get('/avaliacoes/{instrumento}', [CentralEnsinoController::class, 'instrumento'])
            ->whereNumber('instrumento')->name('instrumento');
        Route::post('/avaliacoes/{instrumento}/questoes', [EnsinoAvaliacoesController::class, 'associarQuestao'])
            ->whereNumber('instrumento')->middleware('throttle:20,1')->name('instrumento.questoes');
        Route::post('/avaliacoes/{instrumento}/publicar', [EnsinoAvaliacoesController::class, 'publicar'])
            ->whereNumber('instrumento')->middleware('throttle:10,1')->name('instrumento.publicar');
        Route::get('/avaliacoes/{instrumento}/previa', [EnsinoAvaliacoesController::class, 'previa'])
            ->whereNumber('instrumento')->name('previa');
        Route::post('/avaliacoes/{instrumento}/previa', [EnsinoAvaliacoesController::class, 'corrigirPrevia'])
            ->whereNumber('instrumento')->middleware('throttle:20,1')->name('previa.corrigir');
        Route::get('/questoes', [CentralEnsinoController::class, 'questoes'])->name('questoes');
        Route::post('/questoes', [EnsinoAvaliacoesController::class, 'salvarQuestao'])->middleware('throttle:20,1')->name('questoes.salvar');
        Route::put('/questoes/{questao}', [EnsinoAvaliacoesController::class, 'salvarQuestao'])
            ->whereNumber('questao')->middleware('throttle:20,1')->name('questoes.atualizar');
        Route::post('/questoes/sugerir', [EnsinoAvaliacoesController::class, 'sugerirQuestao'])
            ->middleware('throttle:5,1')->name('questoes.sugerir');
    });
    Route::prefix('financeiro')->name('secretaria.financeiro.')->group(function () {
        Route::get('/', [CentralFinanceiroController::class, 'index'])->name('painel');
        Route::post('/mensalidades/{mensalidade}/pix', [CentralFinanceiroController::class, 'gerarPix'])
            ->whereNumber('mensalidade')->middleware('throttle:6,1')->name('pix');
        Route::post('/mensalidades/{mensalidade}/boleto', [CentralFinanceiroController::class, 'gerarBoleto'])
            ->whereNumber('mensalidade')->middleware('throttle:6,1')->name('boleto');
        Route::get('/mensalidades/{mensalidade}/recibo', [CentralFinanceiroController::class, 'recibo'])
            ->whereNumber('mensalidade')->name('recibo');
        Route::post('/mensalidades/{mensalidade}/agendar', [CentralFinanceiroController::class, 'agendar'])
            ->whereNumber('mensalidade')->middleware('throttle:10,1')->name('agendar');
        Route::post('/envios/{envio}/cancelar', [CentralFinanceiroController::class, 'cancelarEnvio'])
            ->whereNumber('envio')->middleware('throttle:10,1')->name('envio.cancelar');
        Route::get('/relatorio', [CentralFinanceiroController::class, 'relatorio'])->name('relatorio');
    });
    Route::prefix('documentos')->name('secretaria.documentos.')->group(function () {
        Route::get('/', [CentralDocumentosController::class, 'painel'])->name('painel');
        Route::get('/solicitacoes', [CentralDocumentosController::class, 'solicitacoes'])->name('solicitacoes');
        Route::get('/solicitacoes/{requerimento}', [CentralDocumentosController::class, 'solicitacao'])
            ->whereNumber('requerimento')->name('solicitacao');
        Route::post('/solicitacoes/{requerimento}/responder', [CentralDocumentosController::class, 'responderSolicitacao'])
            ->whereNumber('requerimento')->middleware('throttle:15,1')->name('solicitacao.responder');
        Route::get('/atendimento', [CentralDocumentosController::class, 'atendimento'])->name('atendimento');
        Route::get('/documentacao', [CentralDocumentosController::class, 'documentacao'])->name('documentacao');
        Route::get('/documentacao/{documento}/conferir', [CentralDocumentosController::class, 'conferirDocumento'])
            ->name('documentacao.conferir');
        Route::get('/documentacao/{documento}/arquivo', [CentralDocumentosController::class, 'documentoArquivo'])
            ->name('documentacao.arquivo');
        Route::post('/documentacao/{documento}/decidir', [CentralDocumentosController::class, 'decidirDocumento'])
            ->middleware('throttle:12,1')->name('documentacao.decidir');
        Route::get('/declaracoes', [CentralDocumentosController::class, 'declaracoes'])->name('declaracoes');
        Route::post('/declaracoes', [CentralDocumentosController::class, 'preparar'])
            ->middleware('throttle:12,1')->name('declaracoes.preparar');
        Route::put('/declaracoes/{documento}', [CentralDocumentosController::class, 'atualizar'])
            ->whereNumber('documento')->middleware('throttle:10,1')->name('declaracao.atualizar');
        Route::get('/declaracoes/{documento}', [CentralDocumentosController::class, 'declaracao'])
            ->whereNumber('documento')->name('declaracao');
        Route::get('/declaracoes/{documento}/previa.pdf', [CentralDocumentosController::class, 'pdf'])
            ->whereNumber('documento')->name('declaracao.pdf');
        Route::post('/declaracoes/{documento}/enviar', [CentralDocumentosController::class, 'enviarAprovacao'])
            ->whereNumber('documento')->middleware('throttle:10,1')->name('declaracao.enviar');
        Route::post('/declaracoes/{documento}/revisar', [CentralDocumentosController::class, 'revisar'])
            ->whereNumber('documento')->middleware('throttle:10,1')->name('declaracao.revisar');
        Route::get('/aprovacoes', [CentralDocumentosController::class, 'aprovacoes'])->name('aprovacoes');
        Route::get('/assinaturas', [CentralDocumentosController::class, 'assinaturas'])->name('assinaturas');
        Route::post('/assinaturas', [SecretariaAssinaturasController::class, 'salvar'])
            ->middleware('throttle:8,1')->name('assinaturas.salvar');
        Route::get('/assinaturas/{assinatura}/imagem', [CentralDocumentosController::class, 'assinaturaImagem'])
            ->whereNumber('assinatura')->name('assinaturas.imagem');
        Route::post('/assinaturas/{assinatura}/desativar', [CentralDocumentosController::class, 'desativarAssinatura'])
            ->whereNumber('assinatura')->middleware('throttle:10,1')->name('assinaturas.desativar');
        Route::get('/historico', [CentralDocumentosController::class, 'historico'])->name('historico');
    });
    Route::prefix('matriculas')->name('secretaria.matriculas.')->group(function () {
        Route::get('/painel', [CentralMatriculasController::class, 'painel'])->name('painel');
        Route::get('/movimentacoes', [CentralMatriculasController::class, 'movimentacoes'])->name('movimentacoes');
        Route::get('/', [CentralMatriculasController::class, 'index'])->name('index');
        Route::get('/turmas', [CentralMatriculasController::class, 'turmas'])->name('turmas');
        Route::get('/turmas/{turma}', [CentralMatriculasController::class, 'turma'])
            ->whereNumber('turma')->name('turma');
        Route::get('/{matricula}', [CentralMatriculasController::class, 'show'])
            ->whereNumber('matricula')->name('show');
        Route::post('/{matricula}/corrigir-numero', [CentralMatriculasController::class, 'corrigirNumero'])
            ->middleware('throttle:10,1')->whereNumber('matricula')->name('corrigir-numero');
        Route::post('/{matricula}/vincular-turma', [CentralMatriculasController::class, 'vincularTurma'])
            ->middleware('throttle:10,1')->whereNumber('matricula')->name('vincular-turma');
        Route::post('/{matricula}/transferir-turma', [CentralMatriculasController::class, 'transferirTurma'])
            ->middleware('throttle:10,1')->whereNumber('matricula')->name('transferir-turma');
        Route::post('/{matricula}/situacao', [CentralMatriculasController::class, 'alterarSituacao'])
            ->middleware('throttle:10,1')->whereNumber('matricula')->name('situacao');
    });
});
Route::match(['GET', 'POST'], '/acesso/confirmar-email/{aluno}', [CentralAcessosController::class, 'confirmarEmail'])
    ->middleware(['signed', 'throttle:10,1'])->whereNumber('aluno')->name('secretaria.acessos.email.confirmar');
