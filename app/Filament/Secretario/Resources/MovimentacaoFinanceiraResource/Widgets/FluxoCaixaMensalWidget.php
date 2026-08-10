<?php
// ==========================================
// FluxoCaixaMensalWidget.php
// ==========================================

namespace App\Filament\Secretario\Resources\MovimentacaoFinanceiraResource\Widgets;

use App\Models\MovimentacaoFinanceira;
use Filament\Widgets\ChartWidget;

class FluxoCaixaMensalWidget extends ChartWidget
{
    protected static ?string $heading = '📈 Fluxo de Caixa Mensal (Últimos 12 Meses)';
    protected static ?int $sort = 2;
    protected static ?string $pollingInterval = '60s';
    protected static ?string $maxHeight = '350px';

    protected int | string | array $columnSpan = [
        'default' => 1,
        'md' => 1,
        'xl' => 1,
    ];

    protected function getData(): array
    {
        $meses = [];
        $entradas = [];
        $saidas = [];
        $saldos = [];

        $nomesMeses = [
            1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr',
            5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
            9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez'
        ];

        // Gerar dados dos últimos 12 meses
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $mes = $date->month;
            $ano = $date->year;

            // Nome do mês para o label
            $meses[] = $nomesMeses[$mes] . '/' . substr($ano, 2);

            // Entradas do mês
            $entradaMes = MovimentacaoFinanceira::where('tipo', 'entrada')
                ->where('status', 'efetivado')
                ->whereMonth('data_movimentacao', $mes)
                ->whereYear('data_movimentacao', $ano)
                ->sum('valor') ?? 0;
            $entradas[] = round($entradaMes, 2);

            // Saídas do mês
            $saidaMes = MovimentacaoFinanceira::where('tipo', 'saida')
                ->where('status', 'efetivado')
                ->whereMonth('data_movimentacao', $mes)
                ->whereYear('data_movimentacao', $ano)
                ->sum('valor') ?? 0;
            $saidas[] = round($saidaMes, 2);

            // Saldo do mês
            $saldos[] = round($entradaMes - $saidaMes, 2);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Entradas',
                    'data' => $entradas,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Saídas',
                    'data' => $saidas,
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Saldo',
                    'data' => $saldos,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                    'borderDash' => [5, 5],
                ],
            ],
            'labels' => $meses,
        ];
    }

    protected function getType(): string
    {
        return 'line';
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
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
        ];
    }
}