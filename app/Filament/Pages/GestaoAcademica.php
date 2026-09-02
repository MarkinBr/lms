<?php

namespace App\Filament\Pages;

use App\Services\GestaoAcademica\PainelAcademicoQuery;
use Filament\Pages\Page;

/**
 * Central operacional acadêmica completa do administrador (Issues #58 e #60).
 *
 * Único item acadêmico visível no menu do painel /admin. Os Resources
 * acadêmicos continuam com rotas ativas para deep links e ações, mas ficam
 * fora do menu para evitar entradas duplicadas.
 *
 * Desempenho e privacidade:
 * - nenhuma coleção Eloquent em propriedade pública;
 * - a árvore é carregada apenas para o curso selecionado;
 * - a seção ativa é validada contra uma lista permitida e refletida em
 *   query string (?secao=...), com fallback para "visao-geral".
 */
class GestaoAcademica extends Page
{
    /**
     * Usa apenas a base de assets/autenticação do Filament. A interface da
     * Central possui navegação e cabeçalho próprios, sem o chrome do painel.
     */
    protected static string $layout = 'filament-panels::components.layout.base';

    protected array $extraBodyAttributes = [
        'class' => 'ga-standalone-body',
    ];

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Gestão Acadêmica';

    protected static ?string $title = 'Gestão Acadêmica';

    protected static ?string $navigationGroup = 'Acadêmico';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.gestao-academica';

    public const SECAO_PADRAO = 'visao-geral';

    public const SECOES_PERMITIDAS = [
        'visao-geral',
        'cursos',
        'estrutura',
        'operacao',
        'pessoas',
        'acompanhamento',
        'documentos',
    ];

    /**
     * Seção ativa da navegação local, validada no servidor.
     */
    public string $secao = self::SECAO_PADRAO;

    public ?int $cursoId = null;

    public string $buscaCurso = '';

    /**
     * Espelha a seção e o curso selecionado na query string.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function queryString(): array
    {
        return [
            'secao' => [
                'as' => 'secao',
                'except' => self::SECAO_PADRAO,
            ],
            'cursoId' => [
                'as' => 'curso',
                'except' => null,
            ],
        ];
    }

    public static function canAccess(): bool
    {
        $usuario = auth()->user();

        return (bool) $usuario && (bool) $usuario->ativo && $usuario->nivel === 'admin';
    }

    public function mount(PainelAcademicoQuery $consulta): void
    {
        $this->secao = $this->normalizarSecao(
            request()->query('secao', self::SECAO_PADRAO)
        );

        $this->cursoId = $this->resolverCursoInicial($consulta);
    }

    public function trocarSecao(string $secao): void
    {
        $this->secao = $this->normalizarSecao($secao);
    }

    public function updatedSecao(string $valor): void
    {
        $this->secao = $this->normalizarSecao($valor);
    }

    public function updatedCursoId(mixed $valor): void
    {
        $this->cursoId = $this->normalizarCursoId($valor);
    }

    public function updatedBuscaCurso(): void
    {
        $consulta = app(PainelAcademicoQuery::class);

        $ids = $consulta->cursosParaSelecao($this->buscaCurso)->pluck('id');

        if ($this->cursoId !== null && ! $ids->contains($this->cursoId)) {
            $this->cursoId = $ids->first();
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $consulta = app(PainelAcademicoQuery::class);

        $cursos = $consulta->cursosParaSelecao($this->buscaCurso);

        // Garante que a seleção atual respeita o escopo de cursos ativos.
        if ($this->cursoId !== null && ! $cursos->contains('id', $this->cursoId)) {
            $cursoValido = $consulta->cursoAtivoValido($this->cursoId);

            $this->cursoId = $cursoValido ? $this->cursoId : $cursos->first()?->id;
        }

        return [
            'estatisticas' => $consulta->resumo(),
            'pendencias' => $consulta->pendencias(),
            'cursos' => $cursos,
            'cursosLista' => $consulta->cursosLista(),
            'proximasAulasAoVivo' => $consulta->proximasAulasAoVivo(),
            'areasIndisponiveis' => $consulta->areasIndisponiveis(),
            'arvore' => $this->cursoId !== null
                ? $consulta->arvoreDoCurso($this->cursoId)
                : null,
        ];
    }

    private function normalizarSecao(mixed $secao): string
    {
        $secao = is_string($secao) ? trim($secao) : '';

        return in_array($secao, self::SECOES_PERMITIDAS, true)
            ? $secao
            : self::SECAO_PADRAO;
    }

    private function resolverCursoInicial(PainelAcademicoQuery $consulta): ?int
    {
        $candidato = $this->normalizarCursoId(request()->query('curso'));

        if ($candidato !== null && $consulta->cursoAtivoValido($candidato)) {
            return $candidato;
        }

        return $consulta->cursoPadraoId();
    }

    private function normalizarCursoId(mixed $valor): ?int
    {
        if ($valor === null || $valor === '' || ! is_numeric($valor)) {
            return null;
        }

        $id = (int) $valor;

        return $id > 0 ? $id : null;
    }
}
