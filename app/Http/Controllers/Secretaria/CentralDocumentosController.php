<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use App\Models\ApplicationDocument;
use App\Models\Matricula;
use App\Models\Requerimento;
use App\Models\SecretariaAssinatura;
use App\Models\SecretariaDocumento;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class CentralDocumentosController extends Controller
{
    private const SITUACOES = ['rascunho', 'em_aprovacao', 'aprovado_interno', 'devolvido'];

    public function painel(): View
    {
        $totais = [
            'solicitacoes' => Requerimento::query()->whereIn('status', ['pendente', 'em_analise'])->count(),
            'documentos_pendentes' => ApplicationDocument::query()->where('validado', false)->whereNull('validado_em')->count(),
            'declaracoes_pendentes' => SecretariaDocumento::query()->where('status', 'em_aprovacao')->count(),
            'declaracoes_rascunho' => SecretariaDocumento::query()->whereIn('status', ['rascunho', 'devolvido'])->count(),
        ];
        $solicitacoes = Requerimento::query()->with('aluno:id,name')
            ->whereIn('status', ['pendente', 'em_analise'])->latest('id')->limit(6)->get();
        $declaracoes = SecretariaDocumento::query()->with(['matricula.user:id,name', 'matricula.curso:id,titulo'])
            ->where('status', 'em_aprovacao')->latest('id')->limit(5)->get();

        return view('secretaria.central.documentos.painel', compact('totais', 'solicitacoes', 'declaracoes'));
    }

    public function solicitacoes(Request $request): View
    {
        $busca = mb_substr(trim((string) $request->query('busca', '')), 0, 120);
        $status = (string) $request->query('status', '');
        $solicitacoes = Requerimento::query()->with(['aluno:id,name', 'respondidoPor:id,name'])
            ->when($busca !== '', fn ($q) => $q->where(function ($filtro) use ($busca) {
                $term = '%'.mb_strtolower($busca).'%';
                $filtro->whereRaw('LOWER(assunto) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(protocolo) LIKE ?', [$term])
                    ->orWhereHas('aluno', fn ($u) => $u->whereRaw('LOWER(name) LIKE ?', [$term]));
            }))
            ->when(in_array($status, ['pendente', 'em_analise', 'aprovado', 'resolvido', 'negado', 'cancelado'], true),
                fn ($q) => $q->where('status', $status))
            ->orderByDesc('id')->paginate(20)->withQueryString();

        return view('secretaria.central.documentos.solicitacoes', compact('solicitacoes', 'busca', 'status'));
    }

    public function solicitacao(Requerimento $requerimento): View
    {
        $requerimento->load(['aluno:id,name,email', 'respondidoPor:id,name']);

        return view('secretaria.central.documentos.solicitacao', compact('requerimento'));
    }

    public function responderSolicitacao(Request $request, Requerimento $requerimento): RedirectResponse
    {
        $dados = $request->validate([
            'status' => ['required', Rule::in(['em_analise', 'resolvido', 'negado'])],
            'resposta' => ['required', 'string', 'min:5', 'max:3000'],
            'confirmar_analise' => ['accepted'],
        ]);
        DB::transaction(function () use ($request, $requerimento, $dados) {
            $registro = Requerimento::query()->lockForUpdate()->findOrFail($requerimento->id);
            if (in_array($registro->status, ['resolvido', 'negado', 'cancelado', 'aprovado'], true)) {
                throw ValidationException::withMessages(['status' => 'Este protocolo foi finalizado.']);
            }
            $antes = $registro->status;
            if ($antes === $dados['status']) {
                throw ValidationException::withMessages(['status' => 'Selecione uma situação diferente da atual.']);
            }
            $registro->update([
                'status' => $dados['status'],
                'resposta' => trim($dados['resposta']),
                'respondido_por' => $request->user()->id,
                'respondido_em' => now(),
            ]);
            $this->auditar($request->user()->id, 'requerimento', (string) $registro->id,
                'resposta', $antes, $dados['status'], $dados['resposta']);
        });

        return back()->with('status', 'Resposta registrada no protocolo.');
    }

    public function atendimento(Request $request): View
    {
        $busca = mb_substr(trim((string) $request->query('busca', '')), 0, 120);
        $alunos = User::query()->where('nivel', 'aluno')
            ->when($busca !== '', fn ($q) => $q->where(function ($f) use ($busca) {
                $term = '%'.mb_strtolower($busca).'%';
                $f->whereRaw('LOWER(name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(email) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(COALESCE(matricula_login, \'\')) LIKE ?', [$term]);
            }))
            ->withCount(['matriculas', 'requerimentos'])
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('secretaria.central.documentos.atendimento', compact('alunos', 'busca'));
    }

    public function documentacao(Request $request): View
    {
        $situacao = (string) $request->query('situacao', '');
        $documentos = ApplicationDocument::query()->with('application:id,nome_completo,email')
            ->when($situacao === 'pendentes', fn ($q) => $q->where('validado', false)->whereNull('validado_em'))
            ->when($situacao === 'aprovados', fn ($q) => $q->where('validado', true))
            ->when($situacao === 'rejeitados', fn ($q) => $q->where('validado', false)->whereNotNull('validado_por_id'))
            ->orderByDesc('created_at')->paginate(16)->withQueryString();

        return view('secretaria.central.documentos.documentacao', compact('documentos', 'situacao'));
    }

    public function conferirDocumento(ApplicationDocument $documento): View
    {
        $documento->load('application:id,nome_completo,email');

        return view('secretaria.central.documentos.conferir', compact('documento'));
    }

    public function documentoArquivo(ApplicationDocument $documento): BinaryFileResponse
    {
        $disco = (string) config('filesystems.applications_disk');
        abort_if($disco === '' || $disco === 'public', 503);
        $caminho = $documento->caminho_arquivo;
        $prefixo = 'applications/'.$documento->application_id.'/';
        abort_unless(is_string($caminho) && str_starts_with($caminho, $prefixo), 404);
        $mime = $documento->mime_type;
        abort_unless(in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true), 415);
        $storage = Storage::disk($disco);
        abort_unless($storage->exists($caminho), 404);

        return response()->file($storage->path($caminho), [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="documento-conferencia.'.match ($mime) {
                'application/pdf' => 'pdf', 'image/png' => 'png', default => 'jpg',
            }.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
        ]);
    }

    public function decidirDocumento(Request $request, ApplicationDocument $documento): RedirectResponse
    {
        $dados = $request->validate([
            'decisao' => ['required', Rule::in(['aprovar', 'rejeitar'])],
            'conferiu_arquivo' => ['accepted'],
            'motivo' => ['required_if:decisao,rejeitar', 'nullable', 'string', 'min:10', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $documento, $dados) {
            $registro = ApplicationDocument::query()->lockForUpdate()->findOrFail($documento->id);
            if ($registro->validado_em !== null) {
                throw ValidationException::withMessages(['decisao' => 'O documento já foi analisado; atualize a lista.']);
            }
            $aprovado = $dados['decisao'] === 'aprovar';
            $registro->update([
                'validado' => $aprovado,
                'validado_por_id' => $request->user()->id,
                'validado_em' => now(),
                'observacoes_validacao' => $aprovado ? 'Conferido pela Secretaria.' : trim($dados['motivo']),
            ]);
            $this->auditar($request->user()->id, 'documento_inscricao', $registro->id,
                'conferencia', 'pendente', $aprovado ? 'aprovado' : 'rejeitado', $dados['motivo'] ?? 'Conferido.');
        });

        return redirect()->route('secretaria.documentos.documentacao')->with('status', 'Conferência registrada.');
    }

    public function declaracoes(Request $request): View
    {
        $situacao = (string) $request->query('situacao', '');
        $documentos = SecretariaDocumento::query()->with(['matricula.user:id,name', 'matricula.curso:id,titulo'])
            ->when(in_array($situacao, self::SITUACOES, true), fn ($q) => $q->where('status', $situacao))
            ->latest('id')->paginate(18)->withQueryString();
        $matriculas = Matricula::query()->with(['user:id,name', 'curso:id,titulo'])
            ->orderByDesc('id')->limit(500)->get(['id', 'user_id', 'curso_id', 'numero_matricula']);
        $assinaturas = SecretariaAssinatura::query()->where('responsavel_id', $request->user()->id)
            ->where('ativa', true)->latest('id')->get();

        return view('secretaria.central.documentos.declaracoes', compact('documentos', 'situacao', 'matriculas', 'assinaturas'));
    }

    public function preparar(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'matricula_id' => ['required', 'integer', 'exists:matriculas,id'],
            'requerimento_id' => ['nullable', 'integer', 'exists:requerimentos,id'],
            'tipo' => ['required', Rule::in(array_keys(SecretariaDocumento::TIPOS))],
            'modelo' => ['required', Rule::in(array_keys(SecretariaDocumento::MODELOS))],
            'finalidade' => ['required', 'string', 'min:5', 'max:500'],
            'assinatura_id' => ['nullable', 'integer', 'exists:secretaria_assinaturas,id'],
        ]);
        $matricula = Matricula::query()->findOrFail($dados['matricula_id']);
        if (filled($dados['requerimento_id'] ?? null)) {
            $requerimento = Requerimento::query()->findOrFail($dados['requerimento_id']);
            if ((int) $requerimento->aluno_id !== (int) $matricula->user_id) {
                throw ValidationException::withMessages(['requerimento_id' => 'Protocolo e matrícula devem pertencer ao mesmo aluno.']);
            }
        }
        if (filled($dados['assinatura_id'] ?? null)) {
            $assinatura = SecretariaAssinatura::query()->findOrFail($dados['assinatura_id']);
            if ((int) $assinatura->responsavel_id !== (int) $request->user()->id || $assinatura->ativa === false) {
                throw ValidationException::withMessages(['assinatura_id' => 'Somente sua assinatura ativa pode ser usada na prévia.']);
            }
        }
        $documento = DB::transaction(function () use ($request, $dados) {
            $registro = SecretariaDocumento::query()->create([
                ...$dados,
                'preparado_por' => $request->user()->id,
                'status' => 'rascunho',
            ]);
            $this->auditar($request->user()->id, 'declaracao', (string) $registro->id,
                'criar', null, 'rascunho', 'Documento preparado sem emissão.');

            return $registro;
        });

        return redirect()->route('secretaria.documentos.declaracao', $documento)
            ->with('status', 'Rascunho criado. Revise a prévia antes de encaminhar.');
    }

    public function declaracao(SecretariaDocumento $documento): View
    {
        $documento->load(['matricula.user', 'matricula.curso', 'matricula.turma', 'assinatura.responsavel', 'preparadoPor', 'revisadoPor']);
        $dados = $this->dadosDocumento($documento);
        $historico = DB::table('secretaria_documento_movimentacoes as m')
            ->leftJoin('users as u', 'u.id', '=', 'm.ator_id')
            ->where('m.entidade', 'declaracao')->where('m.entidade_id', (string) $documento->id)
            ->select('m.*', 'u.name as responsavel')->orderByDesc('m.id')->limit(30)->get();

        return view('secretaria.central.documentos.declaracao', compact('documento', 'dados', 'historico'));
    }

    public function pdf(SecretariaDocumento $documento): Response
    {
        $documento->load(['matricula.user', 'matricula.curso', 'matricula.turma', 'assinatura.responsavel']);
        $dados = $this->dadosDocumento($documento);
        $logos = [];
        foreach (['cetsadp' => 'assets/images/cetsadp3.png', 'ieadp' => 'assets/images/ieadp.png'] as $nome => $caminho) {
            $arquivo = public_path($caminho);
            $logos[$nome] = is_file($arquivo) ? 'data:image/png;base64,'.base64_encode(file_get_contents($arquivo)) : null;
        }
        $assinaturaGrafica = null;
        if ($documento->assinatura) {
            $assinatura = $documento->assinatura;
            $arquivo = $assinatura->arquivo_privado;
            if (is_string($arquivo) && str_starts_with($arquivo, 'secretaria/assinaturas/') &&
                Storage::disk('local')->exists($arquivo)) {
                $assinaturaGrafica = 'data:'.$assinatura->mime.';base64,'.base64_encode(Storage::disk('local')->get($arquivo));
            }
        }
        $pdf = Pdf::loadView('pdf.secretaria-documento-previa', compact('documento', 'dados', 'logos', 'assinaturaGrafica'))
            ->setPaper('a4');

        return $pdf->stream('previa-documento-'.$documento->id.'.pdf');
    }

    public function enviarAprovacao(Request $request, SecretariaDocumento $documento): RedirectResponse
    {
        $request->validate(['confirmar_envio' => ['accepted']]);
        DB::transaction(function () use ($request, $documento) {
            $registro = SecretariaDocumento::query()->lockForUpdate()->findOrFail($documento->id);
            if (in_array($registro->status, ['rascunho', 'devolvido'], true) === false) {
                throw ValidationException::withMessages(['confirmar_envio' => 'O documento não está disponível para envio.']);
            }
            $registro->load('matricula.user', 'matricula.curso', 'matricula.turma');
            $registro->update([
                'status' => 'em_aprovacao',
                'enviado_em' => now(),
                'revisado_por' => null,
                'revisado_em' => null,
                'dados_congelados' => $this->dadosAtuais($registro),
            ]);
            $this->auditar($request->user()->id, 'declaracao', (string) $registro->id,
                'enviar_aprovacao', 'rascunho', 'em_aprovacao', 'Dados acadêmicos congelados na revisão.');
        });

        return back()->with('status', 'Enviado para conferência de outro responsável. Não houve emissão oficial.');
    }

    public function revisar(Request $request, SecretariaDocumento $documento): RedirectResponse
    {
        $dados = $request->validate([
            'decisao' => ['required', Rule::in(['aprovar', 'devolver'])],
            'conferiu_previa' => ['accepted'],
            'motivo' => ['required_if:decisao,devolver', 'nullable', 'string', 'min:10', 'max:1000'],
        ]);
        DB::transaction(function () use ($request, $documento, $dados) {
            $registro = SecretariaDocumento::query()->lockForUpdate()->findOrFail($documento->id);
            if ($registro->status !== 'em_aprovacao') {
                throw ValidationException::withMessages(['decisao' => 'Este documento não está aguardando aprovação.']);
            }
            if ((int) $registro->preparado_por === (int) $request->user()->id) {
                throw ValidationException::withMessages(['decisao' => 'A revisão deve ser realizada por outra pessoa da equipe.']);
            }
            $aprovado = $dados['decisao'] === 'aprovar';
            $estadoNovo = $aprovado ? 'aprovado_interno' : 'devolvido';
            $registro->update([
                'status' => $estadoNovo,
                'revisado_por' => $request->user()->id,
                'revisado_em' => now(),
                'observacao_revisao' => $dados['motivo'] ?? 'Conferência interna concluída.',
            ]);
            $this->auditar($request->user()->id, 'declaracao', (string) $registro->id,
                'revisao', 'em_aprovacao', $estadoNovo, $dados['motivo'] ?? 'Conferência interna.');
        });

        return redirect()->route('secretaria.documentos.aprovacoes')
            ->with('status', 'Revisão interna registrada. O documento ainda não tem validade oficial.');
    }

    public function aprovacoes(): View
    {
        $declaracoes = SecretariaDocumento::query()->where('status', 'em_aprovacao')
            ->with(['matricula.user:id,name', 'matricula.curso:id,titulo', 'preparadoPor:id,name'])
            ->latest('id')->paginate(12);
        $documentos = ApplicationDocument::query()
            ->where('validado', false)->whereNull('validado_em')
            ->with('application:id,nome_completo,email')->latest('created_at')->limit(15)->get();

        return view('secretaria.central.documentos.aprovacoes', compact('declaracoes', 'documentos'));
    }

    public function assinaturas(Request $request): View
    {
        $assinaturas = SecretariaAssinatura::query()
            ->where('responsavel_id', $request->user()->id)->latest('id')->get();

        return view('secretaria.central.documentos.assinaturas', compact('assinaturas'));
    }

    public function assinaturaImagem(Request $request, SecretariaAssinatura $assinatura): BinaryFileResponse
    {
        abort_unless((int) $assinatura->responsavel_id === (int) $request->user()->id, 403);
        $caminho = $assinatura->arquivo_privado;
        abort_unless(is_string($caminho) && str_starts_with($caminho, 'secretaria/assinaturas/'), 404);
        $disco = Storage::disk('local');
        abort_unless($disco->exists($caminho), 404);

        return response()->file($disco->path($caminho), [
            'Content-Type' => $assinatura->mime,
            'Content-Disposition' => 'inline; filename="assinatura.png"',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }

    public function desativarAssinatura(Request $request, SecretariaAssinatura $assinatura): RedirectResponse
    {
        abort_unless((int) $assinatura->responsavel_id === (int) $request->user()->id, 403);
        $assinatura->update(['ativa' => false]);

        return back()->with('status', 'Assinatura desativada para novos rascunhos. O histórico foi preservado.');
    }

    public function historico(Request $request): View
    {
        $entidade = (string) $request->query('entidade', '');
        $movimentacoes = DB::table('secretaria_documento_movimentacoes as m')
            ->leftJoin('users as u', 'u.id', '=', 'm.ator_id')
            ->select('m.*', 'u.name as responsavel')
            ->when(in_array($entidade, ['declaracao', 'documento_inscricao', 'requerimento'], true),
                fn ($q) => $q->where('entidade', $entidade))
            ->orderByDesc('m.id')->paginate(25)->withQueryString();

        return view('secretaria.central.documentos.historico', compact('movimentacoes', 'entidade'));
    }

    private function dadosDocumento(SecretariaDocumento $documento): array
    {
        return $documento->dados_congelados ?? $this->dadosAtuais($documento);
    }

    private function dadosAtuais(SecretariaDocumento $documento): array
    {
        $matricula = $documento->matricula;

        return [
            'estudante' => $matricula->user?->name ?? 'Não informado',
            'numero_matricula' => $matricula->numero_matricula ?? 'Não informado',
            'curso' => $matricula->curso?->titulo ?? 'Não informado',
            'turma' => $matricula->turma?->nome ?? 'Não informada',
            'situacao' => ucfirst((string) $matricula->status),
            'data_matricula' => $matricula->data_matricula?->format('d/m/Y') ?? 'Não informada',
        ];
    }

    private function auditar(int $ator, string $entidade, string $id, string $acao,
        ?string $anterior, ?string $novo, ?string $motivo): void
    {
        DB::table('secretaria_documento_movimentacoes')->insert([
            'entidade' => $entidade, 'entidade_id' => $id, 'ator_id' => $ator,
            'acao' => $acao, 'estado_anterior' => $anterior, 'estado_novo' => $novo,
            'motivo' => $motivo, 'created_at' => now(),
        ]);
    }
}
