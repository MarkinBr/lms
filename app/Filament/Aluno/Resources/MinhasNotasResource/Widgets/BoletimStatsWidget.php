<?php

namespace App\Filament\Aluno\Resources\MinhasNotasResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use App\Models\Nota;

class BoletimStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $notas = Nota::where('aluno_id', Auth::id())->get();
        
        if ($notas->isEmpty()) {
            return [
                Stat::make('Média Geral', '-')
                    ->description('Sem notas lançadas')
                    ->color('gray'),
            ];
        }
        
        $mediaGeral = $notas->avg('media_final');
        $aprovadas = $notas->where('situacao', 'Aprovado')->count();
        $reprovadas = $notas->whereIn('situacao', ['Reprovado', 'Reprovado por Falta'])->count();

        return [
            Stat::make('Média Geral', number_format($mediaGeral, 1))
                ->description('Todas as disciplinas')
                ->descriptionIcon('heroicon-o-academic-cap')
                ->color($mediaGeral >= 7 ? 'success' : ($mediaGeral >= 5 ? 'warning' : 'danger')),

            Stat::make('Disciplinas Aprovadas', $aprovadas)
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Reprovações', $reprovadas)
                ->descriptionIcon('heroicon-o-x-circle')
                ->color($reprovadas > 0 ? 'danger' : 'success'),
        ];
    }
}