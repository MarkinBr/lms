<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\Matricula;
use App\Models\MatriculaTurma;
use App\Models\Turma;
use App\Services\VincularMatriculaTurmaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CentralMatriculasController extends Controller
{
    /**
     * Mesa de trabalho: indicadores e pendências calculados do banco, sem
     * carregar todas as matrículas na memória.
     */
    public function painel(): View
    {
        $semTurma = Matricula::query()
            ->ativas()
            ->whereNull('turma_id')
            ->whereDoesntHave('turmasHistorico', fn ($q) => $q->where('matricula_turma.status', MatriculaTurma::STATUS_ATIVO));

        $turmasAbertas = Turma::query()->where('status', Turma::STATUS_ABERTA)
            ->get(['id', 'nome', 'curso_id', 'capacidade']);
        $ocupacaoPorTurma = $this->ocupacoesTurmas($turmasAbertas->pluck('id')->all());
        $turmasLotadas = $turmasAbertas->filter(fn (Turma $t) => $t->capacidade !== null
            && (int) ($ocupacaoPorTurma->get($t->id) ?? 0) >= (int) $t->capacidade);
        $lotadas = $turmasLotadas->count();
        $turmasLotadas = $turmasLotadas->take(5);
        $turmasLotadas->each(fn (Turma $t) => $t->setAttribute('ocupacao_ativa', (int) ($ocupacaoPorTurma->get($t->id) ?? 0)));

        $totais = [
            'matriculas_ativas' => Matricula::query()->ativas()->count(),
            'sem_turma' => (clone $semTurma)->count(),
            'turmas_abertas' => $turmasAbertas->count(),
            'turmas_lotadas' => $lotadas,
        ];

        $pendencias = (clone $semTurma)
            ->with(['user:id,name', 'curso:id,titulo'])
            ->latest('id')->limit(6)->get();

        $trancadas = Matricula::query()
            ->whereRaw("LOWER(COALESCE(status, '')) IN ('trancada', 'trancado')")
            ->with(['user:id,name', 'curso:id,titulo'])
            ->latest('id')->limit(4)->get();

        $ultimas = DB::table('matricula_movimentacoes as mm')
            ->join('matriculas as m', 'm.id', '=', 'mm.matricula_id')
            ->leftJoin('users as aluno', 'aluno.id', '=', 'm.user_id')
            ->select('mm.id', 'mm.tipo', 'mm.created_at', 'aluno.name as aluno', 'm.numero_matricula')
            ->orderByDesc('mm.id')->limit(5)->get();

        return view('secretaria.central.matriculas.painel', compact('totais', 'pendencias', 'trancadas', 'ultimas', 'turmasLotadas'));
    }

    public function movimentacoes(Request $request): View
    {
        $busca = mb_substr(trim((string) $request->query('busca', '')), 0, 150);
        $tipo = (string) $request->query('tipo', '');

        $historico = DB::table('matricula_movimentacoes as mm')
            ->join('matriculas as m', 'm.id', '=', 'mm.matricula_id')
            ->leftJoin('users as aluno', 'aluno.id', '=', 'm.user_id')
            ->leftJoin('users as ator', 'ator.id', '=', 'mm.ator_id')
            ->select('mm.*', 'm.numero_matricula', 'aluno.name as aluno', 'ator.name as responsavel')
            ->when($busca !== '', function ($query) use ($busca) {
                $term = '%'.mb_strtolower($busca).'%';
                $query->where(function ($q) use ($term) {
                    $q->whereRaw('LOWER(aluno.name) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(m.numero_matricula) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(mm.tipo) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(mm.motivo) LIKE ?', [$term]);
                });
            })
            ->when(in_array($tipo, ['correcao_numero', 'vinculo_turma', 'transferencia_turma', 'situacao_matricula'], true),
                fn ($q) => $q->where('mm.tipo', $tipo))
            ->orderByDesc('mm.id')
            ->paginate(20)->withQueryString();

        return view('secretaria.central.matriculas.movimentacoes', compact('historico', 'busca', 'tipo'));
    }

    public function index(Request $request): View
    {
        $busca = mb_substr(trim((string) $request->query('busca', '')), 0, 150);
        $cursoId = filter_var($request->query('curso'), FILTER_VALIDATE_INT) ?: null;
        $situacao = (string) $request->query('situacao', '');
        $turmaId = (string) $request->query('turma', '');

        $matriculas = Matricula::query()
            ->with(['user:id,name', 'curso:id,titulo', 'turma:id,nome', 'turmasHistorico:id,nome'])
            ->when($busca !== '', function ($query) use ($busca) {
                $term = '%'.mb_strtolower($busca).'%';
                $query->where(function ($q) use ($term) {
                    $q->whereRaw('LOWER(numero_matricula) LIKE ?', [$term])
                        ->orWhereHas('user', fn ($u) => $u->whereRaw('LOWER(name) LIKE ?', [$term]));
                });
            })
            ->when($cursoId, fn ($q) => $q->where('curso_id', $cursoId))
            ->when($turmaId === 'sem', fn ($q) => $q->whereNull('turma_id')
                ->whereDoesntHave('turmasHistorico', fn ($v) => $v->where('matricula_turma.status', MatriculaTurma::STATUS_ATIVO)))
            ->when(ctype_digit($turmaId) && (int) $turmaId > 0, fn ($q) => $q->where(function ($v) use ($turmaId) {
                $v->where('turma_id', (int) $turmaId)->orWhereHas('turmasHistorico', fn ($t) => $t->where('turmas.id', (int) $turmaId));
            }))
            ->when(in_array($situacao, ['ativas', 'trancadas', 'concluidas', 'canceladas'], true),
                fn ($q) => match ($situacao) {
                    'ativas' => $q->ativas(),
                    'trancadas' => $q->whereRaw("LOWER(status) IN ('trancada', 'trancado')"),
                    'concluidas' => $q->whereRaw("LOWER(status) IN ('concluida', 'concluido')"),
                    default => $q->canceladas(),
                })
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(15)->withQueryString();

        $cursos = Curso::query()->orderBy('titulo')->get(['id', 'titulo']);
        $turmasFiltro = Turma::query()->orderBy('nome')->get(['id', 'nome']);
        $totais = [
            'matriculas' => Matricula::count(),
            'ativas' => Matricula::ativas()->count(),
            'sem_turma' => Matricula::query()->whereNull('turma_id')
                ->whereDoesntHave('turmasHistorico', fn ($q) => $q->where('matricula_turma.status', MatriculaTurma::STATUS_ATIVO))
                ->count(),
            'turmas' => Turma::count(),
        ];

        return view('secretaria.central.matriculas.index', compact('matriculas', 'busca', 'cursoId', 'situacao', 'cursos', 'turmasFiltro', 'turmaId', 'totais'));
    }

    public function show(Matricula $matricula): View
    {
        $matricula->load([
            'user:id,name,matricula_login',
            'curso:id,titulo',
            'turma:id,nome,curso_id',
            'turmasHistorico:id,nome,curso_id',
        ]);
        $historico = DB::table('matricula_movimentacoes')
            ->leftJoin('users', 'users.id', '=', 'matricula_movimentacoes.ator_id')
            ->where('matricula_movimentacoes.matricula_id', $matricula->id)
            ->select('matricula_movimentacoes.*', 'users.name as autor')
            ->orderByDesc('matricula_movimentacoes.id')->limit(50)->get();

        $turmasDisponiveis = $matricula->curso_id
            ? Turma::query()->where('curso_id', $matricula->curso_id)
                ->where('ativa', true)->where('status', Turma::STATUS_ABERTA)
                ->orderBy('nome')->get(['id', 'nome', 'capacidade', 'disciplina_id'])
            : collect();

        $outrasMatriculas = $matricula->user_id
            ? Matricula::query()->where('user_id', $matricula->user_id)->whereKeyNot($matricula->id)
                ->with('curso:id,titulo')->orderByDesc('id')->limit(8)->get()
            : collect();

        return view('secretaria.central.matriculas.show',
            compact('matricula', 'historico', 'turmasDisponiveis', 'outrasMatriculas'));
    }

    public function turmas(Request $request): View
    {
        $busca = mb_substr(trim((string) $request->query('busca', '')), 0, 150);
        $cursoId = filter_var($request->query('curso'), FILTER_VALIDATE_INT) ?: null;
        $situacao = (string) $request->query('situacao', '');
        $turmas = Turma::query()
            ->with(['curso:id,titulo', 'professor:id,name', 'periodoLetivo:id,nome', 'disciplina:id,nome'])
            ->withCount(['matriculas as legadas_count', 'matriculasTurmas as vinculos_count'])
            ->when($busca !== '', function ($q) use ($busca) {
                $term = '%'.mb_strtolower($busca).'%';
                $q->where(function ($q) use ($term) {
                    $q->whereRaw('LOWER(nome) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(codigo) LIKE ?', [$term])
                        ->orWhereHas('professor', fn ($p) => $p->whereRaw('LOWER(name) LIKE ?', [$term]))
                        ->orWhereHas('disciplina', fn ($p) => $p->whereRaw('LOWER(nome) LIKE ?', [$term]));
                });
            })
            ->when($cursoId, fn ($q) => $q->where('curso_id', $cursoId))
            ->when(in_array($situacao, Turma::STATUS_PERMITIDOS, true), fn ($q) => $q->where('status', $situacao))
            ->orderByDesc('ano_letivo')->orderBy('nome')
            ->paginate(15)->withQueryString();
        $cursos = Curso::query()->orderBy('titulo')->get(['id', 'titulo']);
        $ocupacoes = $this->ocupacoesTurmas($turmas->getCollection()->pluck('id')->all());
        $turmas->getCollection()->each(fn (Turma $turma) => $turma->setAttribute(
            'ocupacao_ativa', (int) ($ocupacoes->get($turma->id) ?? 0)
        ));

        return view('secretaria.central.matriculas.turmas', compact('turmas', 'busca', 'cursoId', 'situacao', 'cursos'));
    }

    public function turma(Turma $turma): View
    {
        $turma->load(['curso:id,titulo', 'professor:id,name', 'periodoLetivo:id,nome', 'disciplina:id,nome']);
        $matriculas = Matricula::query()->with([
            'user:id,name',
            'turmasHistorico' => fn ($q) => $q->where('turmas.id', $turma->id),
        ])
            ->where(function ($q) use ($turma) {
                $q->where('turma_id', $turma->id)
                    ->orWhereHas('turmasHistorico', fn ($v) => $v->where('turmas.id', $turma->id));
            })
            ->orderByDesc('created_at')->paginate(25);

        $ocupacaoAtiva = (int) ($this->ocupacoesTurmas([$turma->id])->get($turma->id) ?? 0);

        return view('secretaria.central.matriculas.turma', compact('turma', 'matriculas', 'ocupacaoAtiva'));
    }

    /**
     * Corrige SOMENTE numero_matricula. Nunca altera users.matricula_login.
     * Lock advisory PostgreSQL protege a verificacao de unicidade concorrente
     * sem impor uma constraint arriscada sobre dados antigos.
     */
    public function corrigirNumero(Request $request, Matricula $matricula): RedirectResponse
    {
        $dados = $request->validate([
            'numero_matricula' => ['required', 'string', 'max:20', 'regex:~^[A-Za-z0-9._/-]+$~D'],
            'numero_atual' => ['present', 'nullable', 'string', 'max:100'],
            'motivo' => ['required', 'string', 'min:5', 'max:500'],
            'senha_confirmacao' => ['required', 'string'],
        ]);
        $this->confirmarSenha($request);
        $numero = trim($dados['numero_matricula']);
        $motivo = trim($dados['motivo']);

        DB::transaction(function () use ($matricula, $request, $numero, $motivo, $dados) {
            $atual = Matricula::query()->lockForUpdate()->findOrFail($matricula->id);
            abort_unless($atual->user?->nivel === 'aluno', 403);
            if ((string) ($atual->numero_matricula ?? '') !== (string) ($dados['numero_atual'] ?? '')) {
                throw ValidationException::withMessages(['numero_matricula' => 'A matrícula foi alterada. Atualize a página antes de corrigir.']);
            }
            if ((string) $atual->numero_matricula === $numero) {
                throw ValidationException::withMessages(['numero_matricula' => 'Informe um número diferente do atual.']);
            }

            if (DB::getDriverName() === 'pgsql') {
                $hash = unpack('N2', hex2bin(substr(hash('sha256', 'academico:numero:'.mb_strtolower($numero)), 0, 16)));
                $partes = array_map(fn ($parte) => $parte > 2147483647 ? $parte - 4294967296 : $parte, array_values($hash));
                DB::select('SELECT pg_advisory_xact_lock(?, ?)', $partes);
            }
            if (Matricula::query()->whereKeyNot($atual->id)
                ->whereRaw('LOWER(TRIM(numero_matricula)) = ?', [mb_strtolower($numero)])->exists()) {
                throw ValidationException::withMessages(['numero_matricula' => 'Esse número já está registrado em outra matrícula.']);
            }

            $anterior = $atual->numero_matricula;
            $atual->numero_matricula = $numero;
            $atual->save();

            $this->auditar((int) $request->user()->id, $atual->id, null,
                'correcao_numero', $anterior, $numero, $motivo);
        });

        return redirect()->route('secretaria.matriculas.show', $matricula)
            ->with('status', 'Número acadêmico atualizado. O login do aluno não foi alterado.');
    }

    /** Acrescenta vínculo sem remover ou sobrescrever históricos anteriores. */
    public function vincularTurma(Request $request, Matricula $matricula, VincularMatriculaTurmaService $vinculos): RedirectResponse
    {
        $dados = $request->validate([
            'turma_id' => ['required', 'integer', 'exists:turmas,id'],
            'motivo' => ['required', 'string', 'min:5', 'max:500'],
            'senha_confirmacao' => ['required', 'string'],
        ]);
        $this->confirmarSenha($request);

        DB::transaction(function () use ($matricula, $vinculos, $request, $dados) {
            $matriculaAtual = Matricula::query()->lockForUpdate()->findOrFail($matricula->id);
            $turma = Turma::query()->lockForUpdate()->findOrFail($dados['turma_id']);
            abort_unless($matriculaAtual->user?->nivel === 'aluno', 403);
            if (! in_array(mb_strtolower((string) $matriculaAtual->status), ['ativo', 'ativa'], true)) {
                throw ValidationException::withMessages(['turma_id' => 'A matrícula precisa estar ativa para receber um novo vínculo.']);
            }
            if (! $turma->ativa || $turma->status !== Turma::STATUS_ABERTA) {
                throw ValidationException::withMessages(['turma_id' => 'A turma precisa estar aberta para receber alunos.']);
            }
            if ((int) $matriculaAtual->turma_id === (int) $turma->id ||
                MatriculaTurma::query()->where('matricula_id', $matriculaAtual->id)->where('turma_id', $turma->id)->exists()) {
                throw ValidationException::withMessages(['turma_id' => 'O aluno já possui vínculo com esta turma.']);
            }

            // Para uma disciplina, o aluno deve ter uma turma ativa por vez.
            if ($turma->disciplina_id) {
                $outrasTurmas = $matriculaAtual->turmasHistorico()
                    ->wherePivot('status', MatriculaTurma::STATUS_ATIVO)->pluck('turmas.id')->all();
                if ($matriculaAtual->turma_id) {
                    $outrasTurmas[] = $matriculaAtual->turma_id;
                }
                if (Turma::query()->whereIn('id', $outrasTurmas)->where('disciplina_id', $turma->disciplina_id)->exists()) {
                    throw ValidationException::withMessages(['turma_id' => 'Já existe turma ativa nesta disciplina. Utilize a transferência para preservar o histórico.']);
                }
            }

            // Conta matrículas distintas, considerando legado e novo relacionamento.
            if ($turma->capacidade !== null) {
                $ocupacao = Matricula::query()->where(function ($q) use ($turma) {
                    $q->where('turma_id', $turma->id)
                        ->orWhereHas('turmasHistorico', fn ($v) => $v->where('turmas.id', $turma->id)
                            ->where('matricula_turma.status', MatriculaTurma::STATUS_ATIVO));
                })->ativas()->count();
                if ($ocupacao >= $turma->capacidade) {
                    throw ValidationException::withMessages(['turma_id' => 'A turma já atingiu sua capacidade.']);
                }
            }

            $vinculos->executar($matriculaAtual, $turma, MatriculaTurma::STATUS_ATIVO, trim($dados['motivo']));
            $this->auditar((int) $request->user()->id, $matriculaAtual->id, $turma->id,
                'vinculo_turma', null, $turma->nome, trim($dados['motivo']));
        });

        return redirect()->route('secretaria.matriculas.show', $matricula)
            ->with('status', 'Vínculo com a turma registrado com sucesso.');
    }

    /**
     * Transfere somente o vínculo de uma disciplina, mantendo o anterior
     * cancelado no histórico. Não movimenta mensalidades nem outras turmas.
     */
    public function transferirTurma(Request $request, Matricula $matricula, VincularMatriculaTurmaService $vinculos): RedirectResponse
    {
        $dados = $request->validate([
            'turma_origem_id' => ['required', 'integer', 'exists:turmas,id'],
            'turma_destino_id' => ['required', 'integer', 'different:turma_origem_id', 'exists:turmas,id'],
            'motivo' => ['required', 'string', 'min:5', 'max:500'],
            'senha_confirmacao' => ['required', 'string'],
        ]);
        $this->confirmarSenha($request);

        DB::transaction(function () use ($matricula, $dados, $request, $vinculos) {
            $registro = Matricula::query()->lockForUpdate()->findOrFail($matricula->id);
            abort_unless($registro->user?->nivel === 'aluno', 403);

            if (! in_array(mb_strtolower((string) $registro->status), ['ativo', 'ativa'], true)) {
                throw ValidationException::withMessages(['turma_destino_id' => 'A matrícula precisa estar ativa para realizar a transferência.']);
            }

            $turmas = Turma::query()->whereIn('id', [$dados['turma_origem_id'], $dados['turma_destino_id']])
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $origem = $turmas->get((int) $dados['turma_origem_id']);
            $destino = $turmas->get((int) $dados['turma_destino_id']);
            if (! $origem || ! $destino ||
                (int) $origem->curso_id !== (int) $registro->curso_id ||
                (int) $destino->curso_id !== (int) $registro->curso_id ||
                ! $origem->disciplina_id || (int) $origem->disciplina_id !== (int) $destino->disciplina_id) {
                throw ValidationException::withMessages(['turma_destino_id' => 'A transferência exige turmas do mesmo curso e da mesma disciplina definida.']);
            }
            if (! $destino->ativa || $destino->status !== Turma::STATUS_ABERTA) {
                throw ValidationException::withMessages(['turma_destino_id' => 'A turma de destino precisa estar aberta.']);
            }

            $origemVinculo = MatriculaTurma::query()->where('matricula_id', $registro->id)
                ->where('turma_id', $origem->id)->lockForUpdate()->first();
            $origemLegada = (int) $registro->turma_id === (int) $origem->id;
            if (! $origemLegada && $origemVinculo?->status !== MatriculaTurma::STATUS_ATIVO) {
                throw ValidationException::withMessages(['turma_origem_id' => 'A matrícula não possui vínculo ativo com a turma de origem.']);
            }
            $destinoVinculo = MatriculaTurma::query()->where('matricula_id', $registro->id)
                ->where('turma_id', $destino->id)->lockForUpdate()->first();
            if ((int) $registro->turma_id === (int) $destino->id ||
                ($destinoVinculo && $destinoVinculo->status !== MatriculaTurma::STATUS_CANCELADO)) {
                throw ValidationException::withMessages(['turma_destino_id' => 'Já existe vínculo com a turma de destino.']);
            }

            if ($destino->capacidade !== null) {
                $ocupacao = Matricula::query()->ativas()->where(function ($q) use ($destino) {
                    $q->where('turma_id', $destino->id)
                        ->orWhereHas('turmasHistorico', fn ($v) => $v->where('turmas.id', $destino->id)
                            ->where('matricula_turma.status', MatriculaTurma::STATUS_ATIVO));
                })->count();
                if ($ocupacao >= $destino->capacidade) {
                    throw ValidationException::withMessages(['turma_destino_id' => 'A turma de destino está sem vagas disponíveis.']);
                }
            }

            $motivo = trim($dados['motivo']);
            if ($origemVinculo) {
                $origemVinculo->update(['status' => MatriculaTurma::STATUS_CANCELADO,
                    'observacao' => 'Transferido para '.$destino->nome.'. Motivo: '.$motivo]);
            } else {
                MatriculaTurma::query()->create([
                    'matricula_id' => $registro->id, 'turma_id' => $origem->id,
                    'status' => MatriculaTurma::STATUS_CANCELADO,
                    'observacao' => 'Vínculo legado encerrado por transferência. Motivo: '.$motivo,
                ]);
            }

            // O ponteiro legado, quando apontava à origem, passa a apontar ao destino.
            // Outras turmas/disciplinas vinculadas não são alteradas.
            if ($origemLegada) {
                $registro->turma_id = $destino->id;
                $registro->save();
            }
            if ($destinoVinculo) {
                $destinoVinculo->update(['status' => MatriculaTurma::STATUS_ATIVO]);
            } else {
                $vinculos->executar($registro, $destino, MatriculaTurma::STATUS_ATIVO, $motivo);
            }
            $this->auditar((int) $request->user()->id, $registro->id, $destino->id,
                'transferencia_turma', $origem->nome, $destino->nome, $motivo);
        });

        return redirect()->route('secretaria.matriculas.show', $matricula)
            ->with('status', 'Transferência registrada. Confira possíveis impactos financeiros e de frequência.');
    }

    /** Não altera cobrança; toda mudança registra motivo e deixa aviso de conferência. */
    public function alterarSituacao(Request $request, Matricula $matricula): RedirectResponse
    {
        $dados = $request->validate([
            'situacao' => ['required', 'in:ativa,trancada,concluida,cancelada'],
            'situacao_anterior' => ['required', 'string', 'max:30'],
            'motivo' => ['required', 'string', 'min:5', 'max:500'],
            'ciente_financeiro' => ['accepted'],
            'senha_confirmacao' => ['required', 'string'],
        ]);
        $this->confirmarSenha($request);

        DB::transaction(function () use ($request, $dados, $matricula) {
            $registro = Matricula::query()->lockForUpdate()->findOrFail($matricula->id);
            abort_unless($registro->user?->nivel === 'aluno', 403);
            $anterior = (string) $registro->status;
            if ($anterior !== $dados['situacao_anterior']) {
                throw ValidationException::withMessages(['situacao' => 'A situação mudou. Reabra a ficha antes de continuar.']);
            }
            $situacaoNormalizada = match (mb_strtolower($anterior)) {
                'ativo', 'ativa' => 'ativa',
                'trancado', 'trancada' => 'trancada',
                'concluido', 'concluida' => 'concluida',
                'cancelado', 'cancelada' => 'cancelada',
                default => null,
            };
            if (! $situacaoNormalizada || $dados['situacao'] === $situacaoNormalizada) {
                throw ValidationException::withMessages(['situacao' => 'Selecione uma situação diferente da atual.']);
            }
            // Não reativa um aluno em turmas que já ultrapassaram a capacidade.
            if ($dados['situacao'] === 'ativa') {
                $turmaIds = $registro->turmasHistorico()
                    ->wherePivot('status', MatriculaTurma::STATUS_ATIVO)->pluck('turmas.id')->all();
                if ($registro->turma_id) {
                    $turmaIds[] = $registro->turma_id;
                }
                foreach (Turma::query()->whereIn('id', array_unique($turmaIds))->orderBy('id')->lockForUpdate()->get() as $turma) {
                    if (! $turma->ativa || $turma->status !== Turma::STATUS_ABERTA) {
                        throw ValidationException::withMessages(['situacao' => 'Existe uma turma encerrada ou indisponível; regularize o vínculo antes de reativar.']);
                    }
                    if ($turma->capacidade === null) {
                        continue;
                    }
                    $ocupacao = Matricula::query()->ativas()->where(function ($q) use ($turma) {
                        $q->where('turma_id', $turma->id)
                            ->orWhereHas('turmasHistorico', fn ($v) => $v->where('turmas.id', $turma->id)
                                ->where('matricula_turma.status', MatriculaTurma::STATUS_ATIVO));
                    })->count();
                    if ($ocupacao >= $turma->capacidade) {
                        throw ValidationException::withMessages(['situacao' => 'Não é possível reativar: uma turma vinculada está lotada.']);
                    }
                }
            }

            $registro->status = $dados['situacao'];
            if ($dados['situacao'] === 'concluida') {
                $registro->data_conclusao ??= today()->toDateString();
            } elseif ($situacaoNormalizada === 'concluida') {
                $registro->data_conclusao = null;
            }
            $registro->save();
            $this->auditar((int) $request->user()->id, $registro->id, null,
                'situacao_matricula', $anterior, $dados['situacao'], trim($dados['motivo']));
        });

        return redirect()->route('secretaria.matriculas.show', $matricula)
            ->with('status', 'Situação atualizada. Revise os efeitos acadêmicos e financeiros; mensalidades não foram modificadas automaticamente.');
    }

    /** Conta matrículas distintas por turma, sem duplicar vínculos legados. */
    private function ocupacoesTurmas(array $turmaIds): Collection
    {
        if ($turmaIds === []) {
            return collect();
        }

        $legados = DB::table('matriculas')
            ->select('turma_id', DB::raw('id as matricula_id'))
            ->whereIn('turma_id', $turmaIds)
            ->whereRaw("LOWER(COALESCE(status, '')) IN ('ativo', 'ativa')");
        $vinculos = DB::table('matricula_turma as mt')
            ->join('matriculas as m', 'm.id', '=', 'mt.matricula_id')
            ->select('mt.turma_id', 'mt.matricula_id')
            ->whereIn('mt.turma_id', $turmaIds)
            ->where('mt.status', MatriculaTurma::STATUS_ATIVO)
            ->whereRaw("LOWER(COALESCE(m.status, '')) IN ('ativo', 'ativa')");

        return DB::query()->fromSub($legados->union($vinculos), 'ocupacao')
            ->selectRaw('turma_id, COUNT(*) as total')
            ->groupBy('turma_id')
            ->pluck('total', 'turma_id');
    }

    private function confirmarSenha(Request $request): void
    {
        $senha = (string) $request->input('senha_confirmacao', '');
        $request->request->remove('senha_confirmacao');
        $ator = $request->user()->fresh();
        $chave = 'academico:senha:'.$ator->id;
        if (RateLimiter::tooManyAttempts($chave, 5)) {
            throw ValidationException::withMessages(['senha_confirmacao' => 'Muitas tentativas. Aguarde alguns minutos.']);
        }
        if ($senha === '' || ! Hash::check($senha, (string) $ator->password)) {
            RateLimiter::hit($chave, 600);
            throw ValidationException::withMessages(['senha_confirmacao' => 'Confirme sua senha administrativa para continuar.']);
        }
        RateLimiter::clear($chave);
    }

    private function auditar(int $ator, int $matricula, ?int $turma, string $tipo,
        ?string $antes, ?string $depois, string $motivo): void
    {
        DB::table('matricula_movimentacoes')->insert([
            'ator_id' => $ator, 'matricula_id' => $matricula, 'turma_id' => $turma,
            'tipo' => $tipo, 'valor_anterior' => $antes, 'valor_novo' => $depois,
            'motivo' => $motivo, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
