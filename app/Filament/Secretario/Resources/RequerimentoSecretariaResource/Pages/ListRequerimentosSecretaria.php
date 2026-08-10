<?php
// ==========================================
// ListRequerimentosSecretaria.php
// ==========================================
namespace App\Filament\Secretario\Resources\RequerimentoSecretariaResource\Pages;

use App\Filament\Secretario\Resources\RequerimentoSecretariaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListRequerimentosSecretaria extends ListRecords
{
    protected static string $resource = RequerimentoSecretariaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Novo Requerimento')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'todos' => Tab::make('Todos')
                ->badge(fn () => \App\Models\Requerimento::count()),

            'pendentes' => Tab::make('Pendentes')
                ->icon('heroicon-o-clock')
                ->badge(fn () => \App\Models\Requerimento::where('status', 'pendente')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pendente')),

            'em_analise' => Tab::make('Em Análise')
                ->icon('heroicon-o-magnifying-glass')
                ->badge(fn () => \App\Models\Requerimento::where('status', 'em_analise')->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'em_analise')),

            'resolvidos' => Tab::make('Resolvidos')
                ->icon('heroicon-o-check-circle')
                ->badge(fn () => \App\Models\Requerimento::where('status', 'resolvido')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'resolvido')),

            'negados' => Tab::make('Negados')
                ->icon('heroicon-o-x-circle')
                ->badge(fn () => \App\Models\Requerimento::where('status', 'negado')->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'negado')),
        ];
    }
}