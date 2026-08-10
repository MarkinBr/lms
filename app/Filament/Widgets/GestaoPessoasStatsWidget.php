<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GestaoPessoasStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        return [
            Stat::make('Total de Pessoas', User::count())
                ->description('Todos os usuários cadastrados')
                ->descriptionIcon('heroicon-m-users')
                ->chart([7, 12, 15, 18, 22, 25, 30])
                ->color('gray'),

            Stat::make('Administradores', User::where('nivel', 'admin')->count())
                ->description(User::where('nivel', 'admin')->where('ativo', true)->count() . ' ativos')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('danger'),

            Stat::make('Secretários', User::where('nivel', 'secretario')->count())
                ->description(User::where('nivel', 'secretario')->where('ativo', true)->count() . ' ativos')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('warning'),

            Stat::make('Professores', User::where('nivel', 'professor')->count())
                ->description(User::where('nivel', 'professor')->where('ativo', true)->count() . ' ativos')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->chart([15, 18, 22, 25, 30, 35, 40])
                ->color('success'),

            Stat::make('Alunos', User::where('nivel', 'aluno')->count())
                ->description(User::where('nivel', 'aluno')->where('ativo', true)->count() . ' ativos')
                ->descriptionIcon('heroicon-m-user-group')
                ->chart([50, 65, 80, 95, 110, 125, 150])
                ->color('info'),

            Stat::make('Usuários Ativos', User::where('ativo', true)->count())
                ->description(round((User::where('ativo', true)->count() / max(User::count(), 1)) * 100, 1) . '% do total')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Cadastros Hoje', User::whereDate('created_at', today())->count())
                ->description('Novos cadastros nas últimas 24h')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary'),

            Stat::make('Sem PIX', User::whereNull('chave_pix')->whereIn('nivel', ['professor', 'secretario'])->count())
                ->description('Profissionais sem PIX cadastrado')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
