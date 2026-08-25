<?php

namespace App\Filament\Pages;

use App\Services\GestaoAcademica\PainelAcademicoQuery;
use Filament\Pages\Page;

/**
 * Central operacional acadêmica do administrador (Issue #57).
 *
 * Único item acadêmico visível no menu do painel /admin. Os Resources
 * acadêmicos continuam com rotas ativas para deep links e atalhos, mas
 * ficam fora do menu para evitar entradas duplicadas.
 *
 * Desempenho: a árvore é carregada apenas para o curso selecionado, via
 * PainelAcademicoQuery, sem coleções Eloquent em propriedades públicas.
 */
class GestaoAcademica extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Gestão Acadêmica';

    protected static ?string $title = 'Gestão Acadêmica';

    protected static ?string $navigationGroup = 'Acadêmico';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.gestao-academica';

    public ?int $cursoId = null;

    public string $buscaCurso = '';

    public static function canAccess(): bool
    {
        $usuario = auth()->user();

        return (bool) $usuario && (bool) $usuario->ativo && $usuario->nivel === 'admin';
    }

    public function mount(PainelAcademicoQuery $consulta): void
    {
        $this->cursoId = $consulta->cursoPadraoId();
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

        return [
            'estatisticas' => $consulta->resumo(),
            'pendencias' => $consulta->pendencias(),
            'cursos' => $consulta->cursosParaSelecao($this->buscaCurso),
            'arvore' => $this->cursoId !== null
                ? $consulta->arvoreDoCurso($this->cursoId)
                : null,
        ];
    }
}
