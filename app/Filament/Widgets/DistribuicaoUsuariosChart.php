<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;

class DistribuicaoUsuariosChart extends ChartWidget
{
    protected static ?string $heading = 'Distribuição de Usuários por Tipo';
    protected static ?int $sort = 2;
    protected static ?string $pollingInterval = '30s';
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Usuários',
                    'data' => [
                        User::where('nivel', 'admin')->count(),
                        User::where('nivel', 'secretario')->count(),
                        User::where('nivel', 'professor')->count(),
                        User::where('nivel', 'aluno')->count(),
                    ],
                    'backgroundColor' => [
                        'rgb(239, 68, 68)',    // Red - Admin
                        'rgb(251, 146, 60)',   // Orange - Secretário
                        'rgb(34, 197, 94)',    // Green - Professor
                        'rgb(59, 130, 246)',   // Blue - Aluno
                    ],
                    'borderColor' => [
                        'rgb(220, 38, 38)',
                        'rgb(234, 88, 12)',
                        'rgb(22, 163, 74)',
                        'rgb(37, 99, 235)',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => [
                '👑 Administradores',
                '📚 Secretários',
                '🎓 Professores',
                '🎒 Alunos'
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'enabled' => true,
                ],
            ],
            'maintainAspectRatio' => true,
            'responsive' => true,
        ];
    }
}
