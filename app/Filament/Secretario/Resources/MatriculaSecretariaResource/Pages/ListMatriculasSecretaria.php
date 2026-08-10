<?php

namespace App\Filament\Secretario\Resources\MatriculaSecretariaResource\Pages;

use App\Filament\Secretario\Resources\MatriculaSecretariaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListMatriculasSecretaria extends ListRecords
{
    protected static string $resource = MatriculaSecretariaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nova Matrícula')
                ->icon('heroicon-o-plus')
                ->color('success'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'todas' => Tab::make('Todas')
                ->badge(fn () => \App\Models\Matricula::count())
                ->badgeColor('gray'),

            'ativas' => Tab::make('Ativas')
                ->icon('heroicon-o-check-circle')
                ->badge(fn () => \App\Models\Matricula::where('status', 'ativo')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'ativo')),

            'trancadas' => Tab::make('Trancadas')
                ->icon('heroicon-o-pause-circle')
                ->badge(fn () => \App\Models\Matricula::where('status', 'trancado')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'trancado')),

            'concluidas' => Tab::make('Concluídas')
                ->icon('heroicon-o-academic-cap')
                ->badge(fn () => \App\Models\Matricula::where('status', 'concluido')->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'concluido')),

            'canceladas' => Tab::make('Canceladas')
                ->icon('heroicon-o-x-circle')
                ->badge(fn () => \App\Models\Matricula::where('status', 'cancelado')->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelado')),

            'inadimplentes' => Tab::make('Inadimplentes')
                ->icon('heroicon-o-exclamation-triangle')
                ->badge(fn () => \App\Models\Matricula::whereHas('mensalidades', fn ($q) => 
                    $q->where('status', 'Atrasado')
                )->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->whereHas('mensalidades', fn ($q) => 
                        $q->where('status', 'Atrasado')
                    )
                ),
                
            'sem_mensalidades' => Tab::make('Sem Mensalidades')
                ->icon('heroicon-o-document-minus')
                ->badge(fn () => \App\Models\Matricula::doesntHave('mensalidades')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->doesntHave('mensalidades')
                ),
        ];
    }
}