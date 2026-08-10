<?php

namespace App\Filament\Secretario\Widgets;

use App\Models\Matricula;
use App\Models\User;
use App\Models\Mensalidade;
use App\Models\Curso;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SecretariaStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $totalAlunos = User::where('nivel', 'aluno')->count();
        $alunosAtivos = User::where('nivel', 'aluno')->where('ativo', true)->count();
        
        $totalMatriculas = Matricula::count();
        $matriculasAtivas = Matricula::where('status', 'ativo')->count();
        
        $mensalidadesAtrasadas = Mensalidade::where('status', 'Atrasado')->count();
        $totalMensalidadesAtrasadas = Mensalidade::where('status', 'Atrasado')->sum('valor');
        
        $matriculasHoje = Matricula::whereDate('created_at', today())->count();
        $alunosHoje = User::where('nivel', 'aluno')->whereDate('created_at', today())->count();

        return [
            // ALUNOS
            Stat::make('Total de Alunos', $totalAlunos)
                ->description("{$alunosAtivos} ativos")
                ->descriptionIcon('heroicon-m-user-group')
                ->chart([50, 65, 80, 95, 110, 125, $totalAlunos])
                ->color('info'),

            // MATRÍCULAS
            Stat::make('Matrículas Ativas', $matriculasAtivas)
                ->description("Total: {$totalMatriculas}")
                ->descriptionIcon('heroicon-m-document-text')
                ->chart([15, 22, 28, 35, 42, 48, $matriculasAtivas])
                ->color('success'),

            // MENSALIDADES ATRASADAS
            Stat::make('Mensalidades Atrasadas', $mensalidadesAtrasadas)
                ->description('R$ ' . number_format($totalMensalidadesAtrasadas, 2, ',', '.'))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            // MATRÍCULAS HOJE
            Stat::make('Novas Matrículas Hoje', $matriculasHoje)
                ->description('Cadastradas nas últimas 24h')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary'),

            // ALUNOS HOJE
            Stat::make('Novos Alunos Hoje', $alunosHoje)
                ->description('Cadastrados nas últimas 24h')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('success'),

            // CURSOS ATIVOS
            Stat::make('Cursos Disponíveis', Curso::where('ativo', true)->count())
                ->description('Cursos ativos no sistema')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('warning'),

            // MATRÍCULAS TRANCADAS
            Stat::make('Matrículas Trancadas', Matricula::where('status', 'trancado')->count())
                ->description('Requerem atenção')
                ->descriptionIcon('heroicon-m-pause-circle')
                ->color('warning'),

            // TAXA DE ATIVAÇÃO
            Stat::make('Taxa de Ativação', round(($alunosAtivos / max($totalAlunos, 1)) * 100, 1) . '%')
                ->description('Alunos ativos vs total')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
        ];
    }
}