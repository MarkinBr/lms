<?php

namespace App\Filament\Secretario\Resources\MovimentacaoFinanceiraResource\Widgets;

use App\Models\MovimentacaoFinanceira;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Carbon\Carbon;

class FluxoCaixaChartWidget extends ChartWidget
{
    protected static ?int $sort = 2;
    protected static ?string $pollingInterval = '60s';
    protected static ?string $maxHeight = '300px';
    
    // Define o filtro padrão ao carregar
    public ?string $filter = 'month';

    public function getHeading(): string
    {
        return match ($this->filter) {
            'year' => 'Fluxo de Caixa Anual (Mês a Mês)',
            'month' => 'Fluxo de Caixa Mensal (Dia a Dia)',
            default => 'Fluxo de Caixa',
        };
    }

    protected function getFilters(): ?array
    {
        return [
            'month' => '📅 Este Mês',
            'year' => '📅 Este Ano',
        ];
    }

    protected function getData(): array
    {
        $activeFilter = $this->filter;

        if ($activeFilter === 'year') {
            $start = now()->startOfYear();
            $end = now()->endOfYear();
            $trendMethod = 'perMonth'; 
            $dateFormat = 'M'; 
        } else {
            $start = now()->startOfMonth();
            $end = now()->endOfMonth();
            $trendMethod = 'perDay';
            $dateFormat = 'd/m'; 
        }

        // --- QUERY DE ENTRADAS ---
        $entradasQuery = Trend::query(
            MovimentacaoFinanceira::where('tipo', 'entrada')->where('status', 'efetivado')
        )
        ->dateColumn('data_movimentacao') // <--- CORREÇÃO AQUI: Usa a data do pagamento
        ->between(start: $start, end: $end);

        $entradas = $entradasQuery->{$trendMethod}()->sum('valor');

        // --- QUERY DE SAÍDAS ---
        $saidasQuery = Trend::query(
            MovimentacaoFinanceira::where('tipo', 'saida')->where('status', 'efetivado')
        )
        ->dateColumn('data_movimentacao') // <--- CORREÇÃO AQUI: Usa a data do pagamento
        ->between(start: $start, end: $end);

        $saidas = $saidasQuery->{$trendMethod}()->sum('valor');

        return [
            'datasets' => [
                [
                    'label' => 'Entradas',
                    'data' => $entradas->map(fn (TrendValue $value) => $value->aggregate),
                    'borderColor' => '#10b981', 
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4, 
                ],
                [
                    'label' => 'Saídas',
                    'data' => $saidas->map(fn (TrendValue $value) => $value->aggregate),
                    'borderColor' => '#ef4444', 
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $entradas->map(function (TrendValue $value) use ($dateFormat, $activeFilter) {
                $date = Carbon::parse($value->date);
                
                if ($activeFilter === 'year') {
                    return match($date->format('m')) {
                        '01' => 'Jan', '02' => 'Fev', '03' => 'Mar', '04' => 'Abr',
                        '05' => 'Mai', '06' => 'Jun', '07' => 'Jul', '08' => 'Ago',
                        '09' => 'Set', '10' => 'Out', '11' => 'Nov', '12' => 'Dez',
                        default => $date->format($dateFormat)
                    };
                }
                
                return $date->format($dateFormat);
            }),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}