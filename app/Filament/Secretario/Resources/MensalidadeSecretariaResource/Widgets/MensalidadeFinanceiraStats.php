<?php
// ==========================================
// MensalidadeFinanceiraStats.php (WIDGET)
// ==========================================
namespace App\Filament\Secretario\Resources\MensalidadeSecretariaResource\Widgets;

use App\Filament\Secretario\Resources\MensalidadeSecretariaResource\Pages\ListMensalidadesSecretaria;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageTable;

class MensalidadeFinanceiraStats extends BaseWidget
{
    use InteractsWithPageTable;

    protected function getTablePage(): string
    {
        return ListMensalidadesSecretaria::class;
    }

    protected function getStats(): array
    {
        $query = $this->getPageTableQuery();

        $totalPago = (clone $query)->where('status', 'Pago')->sum('valor');
        $totalPendente = (clone $query)->where('status', 'Pendente')->sum('valor');
        $totalAtrasado = (clone $query)->where('status', 'Atrasado')->sum('valor');
        $qtdAtrasadas = (clone $query)->where('status', 'Atrasado')->count();

        return [
            Stat::make('💰 Recebido', 'R$ ' . number_format($totalPago, 2, ',', '.'))
                ->description('Total de mensalidades pagas')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([100, 150, 200, 250, 300]),

            Stat::make('⏳ A Receber', 'R$ ' . number_format($totalPendente, 2, ',', '.'))
                ->description('Total pendente')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('🔴 Em Atraso', 'R$ ' . number_format($totalAtrasado, 2, ',', '.'))
                ->description("{$qtdAtrasadas} mensalidades atrasadas")
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}