<?php

namespace App\Filament\Aluno\Pages;

use App\Models\ChamadaFrequencia;
use App\Models\Matricula;
use App\Models\MatriculaTurma;
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
