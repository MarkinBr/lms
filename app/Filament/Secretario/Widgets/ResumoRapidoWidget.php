<?php
// app/Filament/Secretario/Widgets/ResumoRapidoWidget.php

namespace App\Filament\Secretario\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use App\Models\Matricula;

class ResumoRapidoWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Total de Alunos', User::where('nivel', 'aluno')->count())
                ->description('Alunos cadastrados')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('success')
                ->chart([7, 4, 6, 8, 10, 12, 15]),
                
            Stat::make('Matrículas Ativas', Matricula::where('status', 'ativo')->count())
                ->description('Matrículas vigentes')
                ->descriptionIcon('heroicon-o-academic-cap')
                ->color('primary'),
                
            Stat::make('Novos Hoje', User::whereDate('created_at', today())->count())
                ->description('Cadastros de hoje')
                ->descriptionIcon('heroicon-o-sparkles')
                ->color('warning'),
        ];
    }
}