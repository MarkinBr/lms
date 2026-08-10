<?php
// ==========================================
// FluxoCaixaAnualWidget.php
// ==========================================

namespace App\Filament\Secretario\Resources\MovimentacaoFinanceiraResource\Widgets;

use App\Models\MovimentacaoFinanceira;
use Filament\Widgets\ChartWidget;

class FluxoCaixaAnualWidget extends ChartWidget
{
    protected static ?string $heading = '📊 Comparativo Anual (Últimos 5 Anos)';
    protected static ?int $sort = 3;
    protected static ?string $pollingInterval = '120s';
    protected static ?string $maxHeight = '350px';

    protected function getData(): array
    {
        $anos = [];
        $entradas = [];
        $saidas = [];
        $saldos = [];

        $anoAtual = now()->year;

        // Gerar dados dos últimos 5 anos
        for ($i = 4; $i >= 0; $i--) {
            $ano = $anoAtual - $i;
            $anos[] = (string)$ano;

            // Entradas do ano
            $entradaAno = MovimentacaoFinanceira::where('tipo', 'entrada')
                ->where('status', 'efetivado')
                ->whereYear('data_movimentacao', $ano)
                ->sum('valor') ?? 0;
            $entradas[] = round($entradaAno, 2);

            // Saídas do ano
            $saidaAno = MovimentacaoFinanceira::where('tipo', 'saida')
                ->where('status', 'efetivado')
                ->whereYear('data_movimentacao', $ano)
                ->sum('valor') ?? 0;
            $saidas[] = round($saidaAno, 2);

            // Saldo do ano
            $saldos[] = round($entradaAno - $saidaAno, 2);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Entradas',
                    'data' => $entradas,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.7)',
                    'borderColor' => '#10b981',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Saídas',
                    'data' => $saidas,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.7)',
                    'borderColor' => '#ef4444',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Saldo',
                    'data' => $saldos,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.7)',
                    'borderColor' => '#3b82f6',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $anos,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
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
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
}