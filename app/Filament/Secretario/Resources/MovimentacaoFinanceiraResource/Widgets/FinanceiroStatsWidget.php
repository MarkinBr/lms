<?php

// ==========================================
// FinanceiroStatsWidget.php
// ==========================================

namespace App\Filament\Secretario\Resources\MovimentacaoFinanceiraResource\Widgets;

use App\Filament\Secretario\Resources\MovimentacaoFinanceiraResource\Pages\ListMovimentacaoFinanceiras;
use App\Models\MovimentacaoFinanceira;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Carbon;

class FinanceiroStatsWidget extends BaseWidget
{
    use InteractsWithPageTable;

    protected function getTablePage(): string
    {
        return ListMovimentacaoFinanceiras::class;
    }

    protected function getStats(): array
    {
        $query = $this->getPageTableQuery();

        // Totais filtrados (baseado nos filtros da tabela)
        $totalEntradas = (clone $query)
            ->where('tipo', 'entrada')
            ->where('status', 'efetivado')
            ->sum('valor') ?? 0;
            
        $totalSaidas = (clone $query)
            ->where('tipo', 'saida')
            ->where('status', 'efetivado')
            ->sum('valor') ?? 0;
            
        $saldo = $totalEntradas - $totalSaidas;
        
        // Mês atual
        $mesAtual = now()->month;
        $anoAtual = now()->year;
        
        $entradasMesAtual = MovimentacaoFinanceira::where('tipo', 'entrada')
            ->where('status', 'efetivado')
            ->whereMonth('data_movimentacao', $mesAtual)
            ->whereYear('data_movimentacao', $anoAtual)
            ->sum('valor') ?? 0;
        
        $saidasMesAtual = MovimentacaoFinanceira::where('tipo', 'saida')
            ->where('status', 'efetivado')
            ->whereMonth('data_movimentacao', $mesAtual)
            ->whereYear('data_movimentacao', $anoAtual)
            ->sum('valor') ?? 0;

        $saldoMesAtual = $entradasMesAtual - $saidasMesAtual;

        // Mês anterior para comparação
        $mesAnterior = now()->subMonth();
        
        $entradasMesAnterior = MovimentacaoFinanceira::where('tipo', 'entrada')
            ->where('status', 'efetivado')
            ->whereMonth('data_movimentacao', $mesAnterior->month)
            ->whereYear('data_movimentacao', $mesAnterior->year)
            ->sum('valor') ?? 0;
        
        $saidasMesAnterior = MovimentacaoFinanceira::where('tipo', 'saida')
            ->where('status', 'efetivado')
            ->whereMonth('data_movimentacao', $mesAnterior->month)
            ->whereYear('data_movimentacao', $mesAnterior->year)
            ->sum('valor') ?? 0;

        // Cálculo de variação percentual
        $variacaoEntradas = $entradasMesAnterior > 0 
            ? (($entradasMesAtual - $entradasMesAnterior) / $entradasMesAnterior) * 100 
            : 0;
            
        $variacaoSaidas = $saidasMesAnterior > 0 
            ? (($saidasMesAtual - $saidasMesAnterior) / $saidasMesAnterior) * 100 
            : 0;

        // Chart data dos últimos 6 meses para entradas
        $chartDataEntradas = collect();
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $valor = MovimentacaoFinanceira::where('tipo', 'entrada')
                ->where('status', 'efetivado')
                ->whereMonth('data_movimentacao', $date->month)
                ->whereYear('data_movimentacao', $date->year)
                ->sum('valor') ?? 0;
            $chartDataEntradas->push($valor);
        }

        // Chart data dos últimos 6 meses para saídas
        $chartDataSaidas = collect();
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $valor = MovimentacaoFinanceira::where('tipo', 'saida')
                ->where('status', 'efetivado')
                ->whereMonth('data_movimentacao', $date->month)
                ->whereYear('data_movimentacao', $date->year)
                ->sum('valor') ?? 0;
            $chartDataSaidas->push($valor);
        }

        $nomesMeses = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
        ];

        return [
            Stat::make('💰 Total Entradas', 'R$ ' . number_format($totalEntradas, 2, ',', '.'))
                ->description('Receitas efetivadas')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart($chartDataEntradas->toArray()),

            Stat::make('💸 Total Saídas', 'R$ ' . number_format($totalSaidas, 2, ',', '.'))
                ->description('Despesas efetivadas')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger')
                ->chart($chartDataSaidas->toArray()),

            Stat::make('📊 Saldo', 'R$ ' . number_format($saldo, 2, ',', '.'))
                ->description('Entradas - Saídas')
                ->descriptionIcon($saldo >= 0 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($saldo >= 0 ? 'success' : 'danger'),

            Stat::make('📅 Entradas ' . $nomesMeses[$mesAtual], 'R$ ' . number_format($entradasMesAtual, 2, ',', '.'))
                ->description(
                    $variacaoEntradas > 0 
                        ? '+' . number_format($variacaoEntradas, 1, ',', '.') . '% vs mês anterior'
                        : ($variacaoEntradas < 0 
                            ? number_format($variacaoEntradas, 1, ',', '.') . '% vs mês anterior'
                            : 'Sem variação')
                )
                ->descriptionIcon($variacaoEntradas >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($variacaoEntradas >= 0 ? 'success' : 'warning'),

            Stat::make('📅 Saídas ' . $nomesMeses[$mesAtual], 'R$ ' . number_format($saidasMesAtual, 2, ',', '.'))
                ->description(
                    $variacaoSaidas > 0 
                        ? '+' . number_format($variacaoSaidas, 1, ',', '.') . '% vs mês anterior'
                        : ($variacaoSaidas < 0 
                            ? number_format($variacaoSaidas, 1, ',', '.') . '% vs mês anterior'
                            : 'Sem variação')
                )
                ->descriptionIcon($variacaoSaidas >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($variacaoSaidas <= 0 ? 'success' : 'warning'),

            Stat::make('💵 Saldo ' . $nomesMeses[$mesAtual], 'R$ ' . number_format($saldoMesAtual, 2, ',', '.'))
                ->description($nomesMeses[$mesAtual] . '/' . $anoAtual)
                ->descriptionIcon($saldoMesAtual >= 0 ? 'heroicon-m-arrow-up' : 'heroicon-m-arrow-down')
                ->color($saldoMesAtual >= 0 ? 'success' : 'danger'),
        ];
    }
}