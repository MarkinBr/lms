<?php

namespace App\Services\GestaoAcademica;

use App\Models\Aula;
use App\Models\AulaAoVivo;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Matricula;
use App\Models\Modulo;
use App\Models\PeriodoLetivo;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Centraliza as consultas da central acadêmica do administrador.
 *
 * Regras de desempenho (Issues #58 e #60):
 * - resumo usa apenas contagens agregadas;
 * - a árvore é carregada somente para o curso selecionado;
 * - eager loading restrito aos campos exibidos na tela;
 * - nenhuma coleção completa é mantida em propriedade pública Livewire;
 * - nenhum dado sensível (CPF, telefone, e-mail, endereço) é exposto.
 */
class PainelAcademicoQuery
{
    public const LIMITE_CURSOS_SELECAO = 25;

    public const LIMITE_AMOSTRA_PENDENCIAS = 5;

    public const LIMITE_LISTA_CURSOS = 8;

    public const LIMITE_AULAS_AO_VIVO = 5;

    /**
     * Indicadores reais da visão geral, em contagens agregadas.
     *
     * @return array<string, int>
     */
    public function resumo(): array
    {
        return [
            'cursos' => Curso::query()->where('ativo', true)->count(),
            'modulos' => Modulo::query()->count(),
            'disciplinas' => Disciplina::query()->where('ativa', true)->count(),
            'aulas' => Aula::query()->count(),
            'periodos' => PeriodoLetivo::query()->count(),
            'turmas' => Turma::query()->count(),
            'matriculas_ativas' => Matricula::query()->ativas()->count(),
            'aulas_ao_vivo' => AulaAoVivo::query()->count(),
            'professores' => User::query()->where('nivel', 'professor')->where('ativo', true)->count(),
            'alunos' => User::query()->where('nivel', 'aluno')->where('ativo', true)->count(),
        ];
    }

    /**
     * Pendências estruturais reais, com amostras limitadas para deep links.
     *
     * @return array<string, array{count: int, ids: array<int, int>}>
     */
    public function pendencias(): array
    {
        return [
            'disciplinas_sem_modulo' => $this->pendencia(
                Disciplina::query()->whereNull('modulo_id')
            ),
            'aulas_sem_disciplina' => $this->pendencia(
                Aula::query()->whereNull('disciplina_id')
            ),
            'turmas_sem_periodo' => $this->pendencia(
                Turma::query()->whereNull('periodo_letivo_id')
            ),
            'turmas_sem_disciplina' => $this->pendencia(
                Turma::query()->whereNull('disciplina_id')
            ),
        ];
    }

    /**
     * Cursos ativos para o seletor da árvore curricular (com busca).
     *
     * @return Collection<int, object{id: int, titulo: string}>
     */
    public function cursosParaSelecao(string $busca = ''): Collection
    {
        return Curso::query()
            ->select(['id', 'titulo'])
            ->where('ativo', true)
            ->when(
                filled($busca),
                fn ($query) => $query->where('titulo', 'like', '%'.$busca.'%')
            )
            ->orderBy('titulo')
            ->limit(self::LIMITE_CURSOS_SELECAO)
            ->get();
    }

    public function cursoPadraoId(): ?int
    {
        $id = Curso::query()
            ->where('ativo', true)
            ->orderBy('titulo')
            ->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * Confirma que um curso existe e está ativo (escopo da central).
     */
    public function cursoAtivoValido(?int $cursoId): bool
    {
        if ($cursoId === null || $cursoId <= 0) {
            return false;
        }

        return Curso::query()
            ->where('ativo', true)
            ->whereKey($cursoId)
            ->exists();
    }

    /**
     * Resumo operacional de cursos para a seção "Cursos".
     * Contagens agregadas por subconsulta, sem N+1.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function cursosLista(): Collection
    {
        return Curso::query()
            ->select(['id', 'titulo', 'nivel', 'ativo'])
            ->withCount([
                'modulos',
                'disciplinas',
            ])
            ->where('ativo', true)
            ->orderBy('titulo')
            ->limit(self::LIMITE_LISTA_CURSOS)
            ->get()
            ->map(fn (Curso $curso) => [
                'id' => $curso->id,
                'titulo' => $curso->titulo,
                'nivel' => $curso->nivel,
                'ativo' => (bool) $curso->ativo,
                'modulos_count' => (int) $curso->modulos_count,
                'disciplinas_count' => (int) $curso->disciplinas_count,
            ]);
    }

    /**
     * Próximas aulas ao vivo (não canceladas), com curso e turma.
     * Sem dados sensíveis de pessoas.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function proximasAulasAoVivo(): Collection
    {
        return AulaAoVivo::query()
            ->proximas()
            ->with([
                'curso:id,titulo',
                'turma:id,nome',
            ])
            ->limit(self::LIMITE_AULAS_AO_VIVO)
            ->get()
            ->map(fn (AulaAoVivo $aula) => [
                'id' => $aula->id,
                'titulo' => $aula->titulo,
                'data_hora' => $aula->data_hora?->format('d/m/Y H:i'),
                'modalidade' => $aula->modalidade,
                'status' => $aula->status,
                'curso' => $aula->curso?->titulo,
                'turma' => $aula->turma?->nome,
            ]);
    }

    /**
     * Áreas da referência visual que ainda não possuem fonte administrativa
     * confirmada. São exibidas como indisponíveis, sem dados inventados.
     *
     * @return array<int, array{slug: string, titulo: string, grupo: string, motivo: string}>
     */
    public function areasIndisponiveis(): array
    {
        return [
            [
                'slug' => 'frequencia',
                'titulo' => 'Frequência',
                'grupo' => 'Turmas & Acompanhamento',
                'motivo' => 'A gestão de frequência vive hoje no painel da Secretaria. A visão administrativa somente leitura está em preparação.',
            ],
            [
                'slug' => 'notas',
                'titulo' => 'Notas',
                'grupo' => 'Turmas & Acompanhamento',
                'motivo' => 'As notas são lançadas pelo professor e vistas pelo aluno. A consolidação administrativa está em preparação.',
            ],
            [
                'slug' => 'avaliacoes',
                'titulo' => 'Avaliações',
                'grupo' => 'Avaliação & Documentos',
                'motivo' => 'Não há fonte de domínio de avaliações confirmada neste snapshot. Nenhum dado será inventado.',
            ],
            [
                'slug' => 'certificados',
                'titulo' => 'Certificados',
                'grupo' => 'Avaliação & Documentos',
                'motivo' => 'A emissão de certificados ainda não possui interface administrativa dedicada. Em preparação.',
            ],
            [
                'slug' => 'requerimentos',
                'titulo' => 'Requerimentos',
                'grupo' => 'Avaliação & Documentos',
                'motivo' => 'Os requerimentos são tratados pela Secretaria. A visão administrativa está em preparação.',
            ],
        ];
    }

    /**
     * Árvore Curso → Módulo → Disciplina → Aula de um único curso,
     * com colunas restritas e sem N+1.
     *
     * @return array<string, mixed>|null
     */
    public function arvoreDoCurso(int $cursoId): ?array
    {
        $curso = Curso::query()
            ->select(['id', 'titulo', 'ativo'])
            ->with([
                'modulos' => fn ($query) => $query
                    ->select(['id', 'curso_id', 'titulo', 'ordem'])
                    ->orderBy('ordem')
                    ->with([
                        'disciplinas' => fn ($query) => $query
                            ->select(['id', 'modulo_id', 'codigo', 'nome', 'ordem', 'ativa'])
                            ->orderBy('ordem')
                            ->with([
                                'aulas' => fn ($query) => $query
                                    ->select(['id', 'disciplina_id', 'titulo', 'ordem', 'tipo_conteudo'])
                                    ->orderBy('ordem'),
                            ]),
                    ]),
            ])
            ->find($cursoId);

        if (! $curso) {
            return null;
        }

        $moduloIds = $curso->modulos->pluck('id');

        $aulasLegadas = Aula::query()
            ->select(['id', 'modulo_id', 'titulo', 'ordem'])
            ->whereIn('modulo_id', $moduloIds)
            ->whereNull('disciplina_id')
            ->orderBy('modulo_id')
            ->orderBy('ordem')
            ->get();

        return [
            'curso' => [
                'id' => $curso->id,
                'titulo' => $curso->titulo,
                'modulos_count' => $curso->modulos->count(),
                'disciplinas_count' => $curso->modulos->sum(fn ($m) => $m->disciplinas->count()),
                'aulas_count' => $curso->modulos->sum(
                    fn ($m) => $m->disciplinas->sum(fn ($d) => $d->aulas->count())
                ) + $aulasLegadas->count(),
            ],
            'modulos' => $curso->modulos->map(fn (Modulo $modulo) => [
                'id' => $modulo->id,
                'titulo' => $modulo->titulo,
                'ordem' => $modulo->ordem,
                'disciplinas' => $modulo->disciplinas->map(fn (Disciplina $disciplina) => [
                    'id' => $disciplina->id,
                    'codigo' => $disciplina->codigo,
                    'nome' => $disciplina->nome,
                    'ativa' => (bool) $disciplina->ativa,
                    'aulas' => $disciplina->aulas->map(fn (Aula $aula) => [
                        'id' => $aula->id,
                        'titulo' => $aula->titulo,
                        'tipo_conteudo' => $aula->tipo_conteudo,
                        'ordem' => $aula->ordem,
                    ])->all(),
                ])->all(),
            ])->all(),
            'aulas_legadas' => $aulasLegadas->map(fn (Aula $aula) => [
                'id' => $aula->id,
                'titulo' => $aula->titulo,
                'modulo_id' => $aula->modulo_id,
            ])->all(),
        ];
    }

    /**
     * @param  Builder<Model>  $query
     * @return array{count: int, ids: array<int, int>}
     */
    private function pendencia($query): array
    {
        return [
            'count' => (clone $query)->count(),
            'ids' => $query->limit(self::LIMITE_AMOSTRA_PENDENCIAS)->pluck('id')->all(),
        ];
    }
}
