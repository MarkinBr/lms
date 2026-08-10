<?php

namespace App\Filament\Aluno\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Support\Htmlable;

class MeusCursos extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Meus Cursos';
    protected static ?string $title = 'Meus Cursos';
    protected static ?string $navigationGroup = 'Ensino';
    protected static string $view = 'filament.aluno.pages.meus-cursos';
    protected static ?int $navigationSort = 2;

    public $cursos = [];
    public $filtroStatus = 'todos';
    public $ordenacao = 'recente';
    public $busca = '';

    public function mount(): void
    {
        $this->carregarCursos();
    }

    public function carregarCursos(): void
    {
        $query = \DB::table('matriculas')
            ->join('cursos', 'cursos.id', '=', 'matriculas.curso_id')
            ->where('matriculas.user_id', Auth::id());

        // Filtro por status
        if ($this->filtroStatus !== 'todos') {
            $query->where('matriculas.status', $this->filtroStatus);
        }

        // Busca por nome
        if (!empty($this->busca)) {
            $query->where('cursos.titulo', 'like', "%{$this->busca}%");
        }

        // Ordenação
        switch ($this->ordenacao) {
            case 'alfabetica':
                $query->orderBy('cursos.titulo');
                break;
            case 'progresso':
                // Ordenar por progresso (calculado depois)
                break;
            default: // 'recente'
                $query->orderBy('matriculas.created_at', 'desc');
        }

        $this->cursos = $query->select(
            'cursos.*',
            'matriculas.data_matricula',
            'matriculas.status as status_matricula',
            'matriculas.data_validade'
        )->get()->map(function($curso) {
            // Calcula estatísticas do curso
            $totalAulas = \DB::table('modulos')
                ->join('aulas', 'aulas.modulo_id', '=', 'modulos.id')
                ->where('modulos.curso_id', $curso->id)
                ->count();

            $aulasAssistidas = \DB::table('aula_progresso')
                ->join('aulas', 'aulas.id', '=', 'aula_progresso.aula_id')
                ->join('modulos', 'modulos.id', '=', 'aulas.modulo_id')
                ->where('modulos.curso_id', $curso->id)
                ->where('aula_progresso.aluno_id', Auth::id())
                ->where('aula_progresso.concluida', true)
                ->count();

            $curso->total_aulas = $totalAulas;
            $curso->aulas_assistidas = $aulasAssistidas;
            $curso->progresso = $totalAulas > 0 ? round(($aulasAssistidas / $totalAulas) * 100) : 0;

            // Última aula assistida
            $curso->ultima_aula = \DB::table('aula_progresso')
                ->join('aulas', 'aulas.id', '=', 'aula_progresso.aula_id')
                ->join('modulos', 'modulos.id', '=', 'aulas.modulo_id')
                ->where('modulos.curso_id', $curso->id)
                ->where('aula_progresso.aluno_id', Auth::id())
                ->orderBy('aula_progresso.updated_at', 'desc')
                ->select('aulas.*')
                ->first();

            // Certificado disponível
            $curso->certificado_disponivel = $curso->progresso >= 100;

            return $curso;
        })->toArray();

        // Ordenação por progresso (após calcular)
        if ($this->ordenacao === 'progresso') {
            usort($this->cursos, function($a, $b) {
                return $b->progresso <=> $a->progresso;
            });
        }
    }

    public function updatedFiltroStatus(): void
    {
        $this->carregarCursos();
    }

    public function updatedOrdenacao(): void
    {
        $this->carregarCursos();
    }

    public function updatedBusca(): void
    {
        $this->carregarCursos();
    }
}