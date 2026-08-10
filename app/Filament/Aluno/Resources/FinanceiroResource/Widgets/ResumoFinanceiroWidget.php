<?php

namespace App\Filament\Aluno\Resources\FinanceiroResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use App\Models\Mensalidade;

class ResumoFinanceiroWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // CORREÇÃO AQUI: Trocamos 'aluno_id' por 'user_id'
        $mensalidades = Mensalidade::whereHas('matricula', function($q) {
            $q->where('user_id', Auth::id());
        })->get();
        
        // Cálculos
        $totalPago = $mensalidades->where('status', 'Pago')->sum('valor');
        $totalPendente = $mensalidades->where('status', '!=', 'Pago')->sum('valor');
        
        // Conta quantas estão vencidas
        $vencidas = $mensalidades->where('status', '!=', 'Pago')
                                 ->where('data_vencimento', '<', now())
                                 ->count();

        // Pega a próxima a vencer
        $proximoVencimento = $mensalidades->where('status', '!=', 'Pago')
                                          ->where('data_vencimento', '>=', now())
                                          ->sortBy('data_vencimento')
                                          ->first();

        return [
            Stat::make('Total Pago', 'R$ ' . number_format($totalPago, 2, ',', '.'))
                ->description('Valor total quitado')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success')
                ->chart([100, 200, 300, 400, $totalPago]),

            Stat::make('Mensalidades Vencidas', $vencidas)
                ->description($vencidas > 0 ? 'Regularize sua situação' : 'Parabéns, tudo em dia!')
                ->descriptionIcon($vencidas > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-badge')
                ->color($vencidas > 0 ? 'danger' : 'success'),

            Stat::make('Próximo Vencimento', 
                $proximoVencimento 
                    // Garanta que 'data_vencimento' é um objeto Carbon no seu Model Mensalidade
                    ? \Carbon\Carbon::parse($proximoVencimento->data_vencimento)->format('d/m/Y')
                    : 'Nenhum'
            )
                ->description($proximoVencimento ? 'Valor: R$ ' . number_format($proximoVencimento->valor, 2, ',', '.') : 'Sem cobranças futuras')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('info'),
                
            Stat::make('Valor Pendente', 'R$ ' . number_format($totalPendente, 2, ',', '.'))
                ->description('Total a pagar em aberto')
                ->color('warning'),
        ];
    }
}