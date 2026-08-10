<?php

namespace App\Filament\Aluno\Resources\MinhasNotasResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use App\Models\Nota;

class GraficoDesempenhoWidget extends ChartWidget
{
    protected static ?string $heading = 'Desempenho por Período';
    protected static ?string $pollingInterval = null;

    protected function getData(): array
    {
        // Busca as notas do aluno logado
        $notas = Nota::where('aluno_id', Auth::id())
            ->orderBy('periodo')
            ->get()
            ->groupBy('periodo');

        $periodos = [];
        $medias = [];

        foreach ($notas as $periodo => $notasPeriodo) {
            $periodos[] = $periodo;
            // Calcula a média das médias finais daquele período
            $medias[] = round($notasPeriodo->avg('media_final'), 1);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Média Geral',
                    'data' => $medias,
                    'borderColor' => 'rgb(34, 197, 94)', // Verde
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $periodos,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
    
    // Opcional: Configurações do gráfico
    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'min' => 0,
                    'max' => 10,
                ],
            ],
        ];
    }
}