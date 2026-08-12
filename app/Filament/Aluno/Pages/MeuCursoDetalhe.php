<?php

namespace App\Filament\Aluno\Pages;

use App\Models\Matricula;
use App\Models\MatriculaTurma;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

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
