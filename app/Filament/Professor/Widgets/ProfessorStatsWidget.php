<?php

namespace App\Filament\Professor\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Curso;
use App\Models\Aula;
use App\Models\Matricula;
use App\Models\Progresso;

class ProfessorStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $professorId = auth()->id();

        $totalCursos = Curso::where('instrutor_id', $professorId)->count();
        $totalAulas = Aula::whereHas('modulo.curso', fn($q) => $q->where('instrutor_id', $professorId))->count();
        $totalAlunos = Matricula::whereHas('curso', fn($q) => $q->where('instrutor_id', $professorId))
            ->where('status', 'ativo')
            ->distinct('user_id')
            ->count('user_id');
        $totalConclusoes = Progresso::where('concluida', true)
            ->whereHas('aula.modulo.curso', fn($q) => $q->where('instrutor_id', $professorId))
            ->count();

        return [
            Stat::make('Meus Cursos', $totalCursos)
                ->description('Cursos que você ministra')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('primary')
                ->chart([2, 4, 6, 8, 10, $totalCursos]),

            Stat::make('Total de Aulas', $totalAulas)
                ->description('Aulas criadas nos seus cursos')
                ->descriptionIcon('heroicon-m-play-circle')
                ->color('success')
                ->chart([5, 10, 15, 20, 25, $totalAulas]),

            Stat::make('Alunos Ativos', $totalAlunos)
                ->description('Matriculados nos seus cursos')
                ->descriptionIcon('heroicon-m-users')
                ->color('warning')
                ->chart([3, 6, 9, 12, 15, $totalAlunos]),

            Stat::make('Aulas Concluídas', $totalConclusoes)
                ->description('Conclusões registradas')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('info')
                ->chart([1, 5, 10, 20, 30, $totalConclusoes]),
        ];
    }
}
