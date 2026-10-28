<?php

namespace App\Filament\Aluno\Pages;

use App\Models\Aula;
use App\Models\AulaProgresso;
use App\Models\ChamadaFrequencia;
use App\Models\Disciplina;
use App\Models\Matricula;
use App\Models\MatriculaTurma;
use App\Models\Modulo;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class MeuCursoDetalhe extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'meus-cursos/{matricula}';

    protected static string $view = 'filament.aluno.pages.meu-curso-detalhe';

    /**
     * Registro carregado após a autorização. O nome não pode coincidir com o
     * parâmetro da rota: o Livewire tentaria fazer a vinculação automática
     * antes de executar o mount(), ocultando a resposta 403 da Policy.
     */
    public Matricula $registro;

    /** @var array<string, mixed> */
    public array $dados = [];

    public function mount(int|string $matricula): void
    {
        $registro = Matricula::query()
            ->with([
                'curso:id,titulo,descricao',
                'turma.professor:id,name',
                'turma.periodoLetivo:id,nome',
                'turmasHistorico' => fn ($query) => $query
                    ->wherePivot('status', MatriculaTurma::STATUS_ATIVO)
                    ->with(['professor:id,name', 'periodoLetivo:id,nome']),
            ])
            ->findOrFail($matricula);

        // Autorização no servidor via MatriculaPolicy@view: URL/ID de outro
        // aluno resulta em 403 (ou 404 para ID inexistente).
        $this->authorize('view', $registro);

        $this->registro = $registro;
        $this->dados = MeusCursos::prepararMatricula($registro);
        $this->dados['frequencias'] = static::frequenciasDaMatricula($registro);
        $this->dados['grade'] = static::gradeDaMatricula($registro);
    }

    /**
     * Grade curricular da matrícula já autorizada no mount().
     *
     * Aulas novas aparecem dentro da disciplina; registros legados sem
     * vínculo são preservados em um grupo transitório, sem remapeamento
     * automático nem alteração de histórico ou progresso.
     *
     * @return array<string, mixed>
     */
    public static function gradeDaMatricula(Matricula $matricula): array
    {
        $modulos = $matricula->curso?->modulos()
            ->with([
                'disciplinas' => fn ($query) => $query->orderBy('ordem')->orderBy('id'),
                'aulas' => fn ($query) => $query->orderBy('ordem')->orderBy('id'),
            ])
            ->orderBy('ordem')
            ->orderBy('id')
            ->get() ?? collect();

        $aulaIds = $modulos
            ->flatMap(fn (Modulo $modulo) => $modulo->aulas->pluck('id'))
            ->unique()
            ->values();

        $concluidas = $aulaIds->isEmpty()
            ? []
            : AulaProgresso::query()
                ->where('aluno_id', $matricula->user_id)
                ->whereIn('aula_id', $aulaIds->all())
                ->where('concluida', true)
                ->pluck('aula_id')
                ->flip()
                ->all();

        $grade = $modulos->map(function (Modulo $modulo) use ($concluidas): array {
            $idsDisciplinas = $modulo->disciplinas->pluck('id')->all();

            $formatarAula = static fn (Aula $aula): array => [
                'id' => $aula->id,
                'titulo' => $aula->titulo,
                'duracao' => $aula->duracao,
                'liberada' => (bool) $aula->liberada,
                'concluida' => isset($concluidas[$aula->id]),
            ];

            $disciplinas = $modulo->disciplinas
                ->map(fn (Disciplina $disciplina): array => [
                    'id' => $disciplina->id,
                    'nome' => $disciplina->nome,
                    'aulas' => $modulo->aulas
                        ->where('disciplina_id', $disciplina->id)
                        ->map($formatarAula)
                        ->values()
                        ->all(),
                ])
                ->all();

            // Um módulo pode conter aulas anteriores ao vínculo obrigatório.
            // Também preserva aulas com referência curricular inconsistente.
            $semDisciplina = $modulo->aulas->reject(
                fn (Aula $aula): bool => in_array((int) $aula->disciplina_id, $idsDisciplinas, true)
            );

            if ($semDisciplina->isNotEmpty()) {
                $disciplinas[] = [
                    'id' => null,
                    'nome' => 'Aulas aguardando organização',
                    'aulas' => $semDisciplina->map($formatarAula)->values()->all(),
                ];
            }

            return [
                'id' => $modulo->id,
                'titulo' => $modulo->titulo,
                'total_aulas' => $modulo->aulas->count(),
                'disciplinas' => $disciplinas,
            ];
        });

        return [
            'modulos' => $grade->all(),
            'total_aulas' => $aulaIds->count(),
            'aulas_concluidas' => count($concluidas),
        ];
    }

    /**
     * Resumo real de frequência da própria matrícula, por turma/disciplina.
     * Somente chamadas concluídas entram no cálculo. Somente leitura: nunca
     * cria nem altera dados. Não expõe frequência de outros alunos.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function frequenciasDaMatricula(Matricula $matricula): Collection
    {
        $turmas = MeusCursos::turmasAtivasDaMatricula($matricula);

        if ($turmas->isEmpty()) {
            return collect();
        }

        $turmaIds = $turmas->pluck('id')->all();

        // Chamadas concluídas das turmas do aluno que têm registro para esta
        // matrícula, agrupadas por turma + disciplina.
        $chamadas = ChamadaFrequencia::query()
            ->concluidas()
            ->whereIn('turma_id', $turmaIds)
            ->whereHas('frequencias', fn ($q) => $q->where('matricula_id', $matricula->id))
            ->with(['turma:id,nome,codigo', 'disciplina:id,nome'])
            ->orderBy('data_aula')
            ->get();

        return $chamadas
            ->groupBy(fn (ChamadaFrequencia $c) => $c->turma_id.'-'.$c->disciplina_id)
            ->map(function (Collection $grupo) use ($matricula) {
                /** @var ChamadaFrequencia $primeira */
                $primeira = $grupo->first();

                $resumo = $matricula->resumoFrequencia($primeira->turma_id, $primeira->disciplina_id);

                return [
                    'turma' => $primeira->turma?->nome ?? $primeira->turma?->codigo ?? 'Turma',
                    'disciplina' => $primeira->disciplina?->nome ?? 'Disciplina',
                    'chamadas_concluidas' => $resumo['chamadas_concluidas'],
                    'presentes' => $resumo['presentes'],
                    'faltas' => $resumo['faltas'],
                    'faltas_justificadas' => $resumo['faltas_justificadas'],
                    'percentual' => $resumo['percentual'],
                ];
            })
            ->sortBy(fn (array $item) => mb_strtolower($item['turma'].' '.$item['disciplina']))
            ->values();
    }

    public function getTitle(): string|Htmlable
    {
        return $this->dados['curso_titulo'] ?? 'Minha matrícula';
    }

    public function getHeading(): string|Htmlable
    {
        return $this->getTitle();
    }
}
