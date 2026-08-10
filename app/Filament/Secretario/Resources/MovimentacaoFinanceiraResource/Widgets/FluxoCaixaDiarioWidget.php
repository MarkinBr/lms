<?php
// ==========================================
// FluxoCaixaDiarioWidget.php
// ==========================================

namespace App\Filament\Secretario\Resources\MovimentacaoFinanceiraResource\Widgets;

use App\Models\MovimentacaoFinanceira;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class FluxoCaixaDiarioWidget extends ChartWidget
{
    protected static ?string $heading = '📅 Fluxo de Caixa Diário (Mês Atual)';
    protected static ?int $sort = 3;
    protected static ?string $pollingInterval = '60s';
    protected static ?string $maxHeight = '350px';

    protected int | string | array $columnSpan = [
        'default' => 1,
        'md' => 1,
        'xl' => 1,
    ];

    public ?string $filter = null;

    protected function getData(): array
    {
        // Determinar o mês/ano baseado no filtro
        $mesAno = $this->filter ?? now()->format('Y-m');
        $date = Carbon::createFromFormat('Y-m', $mesAno);
        
        $mes = $date->month;
        $ano = $date->year;
        $diasNoMes = $date->daysInMonth;

        $dias = [];
        $entradas = [];
        $saidas = [];
        $saldos = [];

        // Gerar dados para cada dia do mês
        for ($dia = 1; $dia <= $diasNoMes; $dia++) {
            $dias[] = str_pad($dia, 2, '0', STR_PAD_LEFT);

            // Entradas do dia
            $entradaDia = MovimentacaoFinanceira::where('tipo', 'entrada')
                ->where('status', 'efetivado')
                ->whereDay('data_movimentacao', $dia)
                ->whereMonth('data_movimentacao', $mes)
                ->whereYear('data_movimentacao', $ano)
                ->sum('valor') ?? 0;
            $entradas[] = round($entradaDia, 2);

            // Saídas do dia
            $saidaDia = MovimentacaoFinanceira::where('tipo', 'saida')
                ->where('status', 'efetivado')
                ->whereDay('data_movimentacao', $dia)
                ->whereMonth('data_movimentacao', $mes)
                ->whereYear('data_movimentacao', $ano)
                ->sum('valor') ?? 0;
            $saidas[] = round($saidaDia, 2);

            // Saldo do dia
            $saldos[] = round($entradaDia - $saidaDia, 2);
        }

        $nomesMeses = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
        ];

        static::$heading = '📅 Fluxo de Caixa Diário - ' . $nomesMeses[$mes] . '/' . $ano;

        return [
            'datasets' => [
                [
                    'label' => 'Entradas',
                    'data' => $entradas,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                    'pointRadius' => 2,
                    'pointHoverRadius' => 5,
                ],
                [
                    'label' => 'Saídas',
                    'data' => $saidas,
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                    'pointRadius' => 2,
                    'pointHoverRadius' => 5,
                ],
                [
                    'label' => 'Saldo Diário',
                    'data' => $saldos,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                    'borderDash' => [5, 5],
                    'pointRadius' => 2,
                    'pointHoverRadius' => 5,
                ],
            ],
            'labels' => $dias,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        $filters = [];
        
        // Gerar opções dos últimos 12 meses
        for ($i = 0; $i < 12; $i++) {
            $date = now()->subMonths($i);
            $key = $date->format('Y-m');
            
            $nomesMeses = [
                1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
            ];
            
            $filters[$key] = $nomesMeses[$date->month] . '/' . $date->year;
        }
        
        return $filters;
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
                    'mode' => 'index',
                    'intersect' => false,
                    'callbacks' => [
                        'title' => "function(context) {
                            return 'Dia ' + context[0].label;
                        }",
                        'label' => "function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += 'R$ ' + context.parsed.y.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                            return label;
                        }"
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => "function(value) {
                            return 'R$ ' + value.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                        }"
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                    'ticks' => [
                        'maxRotation' => 0,
                        'autoSkip' => true,
                        'maxTicksLimit' => 15,
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}