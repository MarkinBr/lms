<?php

namespace App\Filament\Secretario\Widgets;

use App\Models\Matricula;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class SecretariaMatriculasChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Evolução de Matrículas (Últimos 30 Dias)';
    protected static ?int $sort = 2;
    protected static ?string $pollingInterval = '60s';
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $data = Trend::model(Matricula::class)
            ->between(
                start: now()->subDays(30),
                end: now(),
            )
            ->perDay()
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Novas Matrículas',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                    'borderColor' => '#3b82f6', // Azul
                    'fill' => 'start',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => $value->date),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}