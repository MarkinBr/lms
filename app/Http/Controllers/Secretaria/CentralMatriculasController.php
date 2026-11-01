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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CentralMatriculasController extends Controller
{
    public function index(Request $request): View
    {
        $busca = mb_substr(trim((string) $request->query('busca', '')), 0, 150);
        $cursoId = filter_var($request->query('curso'), FILTER_VALIDATE_INT) ?: null;
        $situacao = (string) $request->query('situacao', '');

        $matriculas = Matricula::query()
            ->with(['user:id,name', 'curso:id,titulo', 'turma:id,nome'])
            ->when($busca !== '', function ($query) use ($busca) {
                $term = '%'.mb_strtolower($busca).'%';
                $query->where(function ($q) use ($term) {
                    $q->whereRaw('LOWER(numero_matricula) LIKE ?', [$term])
                        ->orWhereHas('user', fn ($u) => $u->whereRaw('LOWER(name) LIKE ?', [$term]));
                });
            })
            ->when($cursoId, fn ($q) => $q->where('curso_id', $cursoId))
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
        $totais = [
            'matriculas' => Matricula::count(),
            'ativas' => Matricula::ativas()->count(),
            'sem_turma' => Matricula::query()->whereNull('turma_id')
                ->whereDoesntHave('turmasHistorico', fn ($q) => $q->where('matricula_turma.status', MatriculaTurma::STATUS_ATIVO))
                ->count(),
            'turmas' => Turma::count(),
        ];

        return view('secretaria.central.matriculas.index', compact('matriculas', 'busca', 'cursoId', 'situacao', 'cursos', 'totais'));
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
                ->orderBy('nome')->get(['id', 'nome', 'capacidade'])
            : collect();

        return view('secretaria.central.matriculas.show', compact('matricula', 'historico', 'turmasDisponiveis'));
    }

    public function turmas(Request $request): View
    {
        $busca = mb_substr(trim((string) $request->query('busca', '')), 0, 150);
        $cursoId = filter_var($request->query('curso'), FILTER_VALIDATE_INT) ?: null;
        $turmas = Turma::query()
            ->with(['curso:id,titulo', 'professor:id,name', 'periodoLetivo:id,nome'])
            ->withCount(['matriculas as legadas_count', 'matriculasTurmas as vinculos_count'])
            ->when($busca !== '', function ($q) use ($busca) {
                $term = '%'.mb_strtolower($busca).'%';
                $q->where(function ($q) use ($term) {
                    $q->whereRaw('LOWER(nome) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(codigo) LIKE ?', [$term]);
                });
            })
            ->when($cursoId, fn ($q) => $q->where('curso_id', $cursoId))
            ->orderByDesc('ano_letivo')->orderBy('nome')
            ->paginate(15)->withQueryString();
        $cursos = Curso::query()->orderBy('titulo')->get(['id', 'titulo']);

        return view('secretaria.central.matriculas.turmas', compact('turmas', 'busca', 'cursoId', 'cursos'));
    }

    public function turma(Turma $turma): View
    {
        $turma->load(['curso:id,titulo', 'professor:id,name', 'periodoLetivo:id,nome']);
        $matriculas = Matricula::query()->with('user:id,name')
            ->where(function ($q) use ($turma) {
                $q->where('turma_id', $turma->id)
                    ->orWhereHas('turmasHistorico', fn ($v) => $v->where('turmas.id', $turma->id));
            })
            ->orderByDesc('created_at')->paginate(25);

        return view('secretaria.central.matriculas.turma', compact('turma', 'matriculas'));
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
