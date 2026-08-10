<?php

namespace App\Filament\Resources\MensalidadeResource\Widgets;

use App\Filament\Resources\MensalidadeResource\Pages\ListMensalidades;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageTable; // <--- Importante para ler o filtro

class MensalidadeStats extends BaseWidget
{
    // Isso conecta o widget à tabela para ele obedecer os filtros
    use InteractsWithPageTable;

    protected function getTablePage(): string
    {
        return ListMensalidades::class;
    }

    protected function getStats(): array
    {
        // Pega a query da tabela JÁ filtrada pelo usuário
        $query = $this->getPageTableQuery();

        // Clona a query para não bugar a contagem de cada status
        $totalPago = (clone $query)->where('status', 'Pago')->sum('valor');
        $totalPendente = (clone $query)->where('status', 'Pendente')->sum('valor');
        $totalAtrasado = (clone $query)->where('status', 'Atrasado')->sum('valor');

        return [
            Stat::make('Recebido (No Período)', 'R$ ' . number_format($totalPago, 2, ',', '.'))
                ->description('Dinheiro em caixa')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('A Receber (Pendente)', 'R$ ' . number_format($totalPendente, 2, ',', '.'))
                ->description('Previsão de entrada')
                ->color('gray'),

            Stat::make('Em Atraso', 'R$ ' . number_format($totalAtrasado, 2, ',', '.'))
                ->description('Cobrança necessária')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}