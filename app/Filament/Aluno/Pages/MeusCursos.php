<?php

namespace App\Filament\Aluno\Pages;

use App\Models\Matricula;
use App\Models\MatriculaTurma;
use App\Models\Turma;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class MeusCursos extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Meus Cursos';

    protected static ?string $title = 'Meus Cursos';

    protected static ?string $navigationGroup = 'Ensino';

    protected static string $view = 'filament.aluno.pages.meus-cursos';

    protected static ?int $navigationSort = 2;

    /**
     * Busca textual livre sobre o título do curso (dados reais).
     */
    public string $busca = '';

    public function getHeading(): string|Htmlable
    {
        $total = $this->matriculas->count();

        $rotulo = $total === 1 ? 'matrícula ativa' : 'matrículas ativas';

        return "Meus Cursos — {$total} {$rotulo}";
    }

    /**
     * Dados preparados para a view: somente matrículas ativas do aluno
     * autenticado, com turmas reais (legado + pivot, sem duplicar).
     */
    public function getMatriculasProperty(): Collection
    {
        $aluno = Auth::user();

        abort_unless($aluno instanceof User, 403);

        return static::matriculasDoAluno($aluno, $this->busca);
    }

    /**
     * Fonte única de consulta da área do aluno. Somente leitura: nunca cria
     * matrículas, turmas, vínculos ou qualquer dado acadêmico.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function matriculasDoAluno(User $aluno, string $busca = ''): Collection
    {
        $matriculas = Matricula::query()
            ->ativas() // compatível com legado: 'ativo'/'ativa', case-insensitive
            ->where('user_id', $aluno->id)
            ->with([
                'curso:id,titulo',
                'turma.professor:id,name',
                'turma.periodoLetivo:id,nome',
                'turmasHistorico' => fn ($query) => $query
                    ->wherePivot('status', MatriculaTurma::STATUS_ATIVO)
                    ->with(['professor:id,name', 'periodoLetivo:id,nome']),
            ])
            ->orderByDesc('data_matricula')
            ->orderByDesc('id')
            ->get();

        $itens = $matriculas->map(fn (Matricula $matricula) => static::prepararMatricula($matricula));

        $termo = trim($busca);

        if ($termo !== '') {
            $itens = $itens->filter(
                fn (array $item) => str_contains(
                    mb_strtolower((string) $item['curso_titulo']),
                    mb_strtolower($termo),
                )
            );
        }

        return $itens->values();
    }

    /**
     * Turmas ativas da matrícula unindo os dois vínculos possíveis:
     * - pivot `matricula_turma` com status ativo (vínculo atual);
     * - coluna legada `matriculas.turma_id`.
     *
     * A mesma turma nunca aparece duas vezes quando ambos existem.
     *
     * @return Collection<int, Turma>
     */
    public static function turmasAtivasDaMatricula(Matricula $matricula): Collection
    {
        $doPivot = $matricula->relationLoaded('turmasHistorico')
            ? $matricula->turmasHistorico->filter(
                fn (Turma $turma) => ($turma->pivot->status ?? null) === MatriculaTurma::STATUS_ATIVO
            )
            : $matricula->turmasHistorico()
                ->wherePivot('status', MatriculaTurma::STATUS_ATIVO)
                ->with(['professor:id,name', 'periodoLetivo:id,nome'])
                ->get();

        $turmas = $doPivot->values();

        $legada = $matricula->relationLoaded('turma')
            ? $matricula->turma
            : $matricula->turma()->with(['professor:id,name', 'periodoLetivo:id,nome'])->first();

        if ($legada instanceof Turma && ! $turmas->contains('id', $legada->id)) {
            $turmas->push($legada);
        }

        return $turmas->values();
    }

    /**
     * Estrutura plana e neutra para renderização: só dados que existem de
     * fato; ausência de turma, período ou docente vira estado neutro.
     *
     * @return array<string, mixed>
     */
    public static function prepararMatricula(Matricula $matricula): array
    {
        $turmas = static::turmasAtivasDaMatricula($matricula);

        return [
            'id' => $matricula->id,
            'numero_matricula' => $matricula->numero_matricula,
            'status' => strtolower((string) $matricula->status),
            'status_label' => 'Ativa',
            'data_matricula' => $matricula->data_matricula?->format('d/m/Y'),
            'curso_titulo' => $matricula->curso?->titulo ?? 'Curso não identificado',
            // Informação institucional fixa do CETSADP: não gera nem lista aulas.
            'modalidade_label' => 'Híbrida (online e presencial)',
            'turmas' => $turmas->map(fn (Turma $turma) => [
                'id' => $turma->id,
                'nome' => $turma->nome ?? $turma->codigo ?? ('Turma #'.$turma->id),
                'codigo' => $turma->codigo,
                'periodo_letivo' => $turma->periodoLetivo?->nome,
                'professor' => $turma->professor?->name,
            ])->all(),
            'possui_turma' => $turmas->isNotEmpty(),
            'detalhe_url' => MeuCursoDetalhe::getUrl(['matricula' => $matricula->id], panel: 'aluno'),
        ];
    }
}
