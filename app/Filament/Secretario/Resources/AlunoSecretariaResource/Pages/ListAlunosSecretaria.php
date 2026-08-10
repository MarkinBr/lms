<?php
// ==========================================
// ListAlunosSecretaria.php
// ==========================================
namespace App\Filament\Secretario\Resources\AlunoSecretariaResource\Pages;

use App\Filament\Secretario\Resources\AlunoSecretariaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListAlunosSecretaria extends ListRecords
{
    protected static string $resource = AlunoSecretariaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Novo Aluno')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'todos' => Tab::make('Todos')
                ->badge(fn () => \App\Models\User::where('nivel', 'aluno')->count()),

            'ativos' => Tab::make('Ativos')
                ->icon('heroicon-o-check-circle')
                ->badge(fn () => \App\Models\User::where('nivel', 'aluno')->where('ativo', true)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('ativo', true)),

            'inativos' => Tab::make('Inativos')
                ->icon('heroicon-o-x-circle')
                ->badge(fn () => \App\Models\User::where('nivel', 'aluno')->where('ativo', false)->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('ativo', false)),

            'com_matriculas' => Tab::make('Com Matrículas')
                ->icon('heroicon-o-document-check')
                ->badge(fn () => \App\Models\User::where('nivel', 'aluno')
                    ->whereHas('matriculas', fn ($q) => $q->where('status', 'ativo'))
                    ->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->whereHas('matriculas', fn ($q) => $q->where('status', 'ativo'))
                ),

            'sem_matriculas' => Tab::make('Sem Matrículas')
                ->icon('heroicon-o-exclamation-triangle')
                ->badge(fn () => \App\Models\User::where('nivel', 'aluno')
                    ->doesntHave('matriculas')
                    ->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->doesntHave('matriculas')),
        ];
    }
}
