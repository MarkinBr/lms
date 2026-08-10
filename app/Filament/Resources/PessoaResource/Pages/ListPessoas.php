<?php

namespace App\Filament\Resources\PessoaResource\Pages;

use App\Filament\Resources\PessoaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPessoas extends ListRecords
{
    protected static string $resource = PessoaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nova Pessoa')
                ->icon('heroicon-o-plus')
                ->color('success'),

            Actions\Action::make('importar')
                ->label('Importar Planilha')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->action(function () {
                    // Implementar importação futura
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'todos' => Tab::make('Todos')
                ->badge(fn () => \App\Models\User::count())
                ->badgeColor('gray'),

            'admin' => Tab::make('Administradores')
                ->icon('heroicon-o-shield-check')
                ->badge(fn () => \App\Models\User::where('nivel', 'admin')->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('nivel', 'admin')),

            'secretario' => Tab::make('Secretários')
                ->icon('heroicon-o-briefcase')
                ->badge(fn () => \App\Models\User::where('nivel', 'secretario')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('nivel', 'secretario')),

            'professor' => Tab::make('Professores')
                ->icon('heroicon-o-academic-cap')
                ->badge(fn () => \App\Models\User::where('nivel', 'professor')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('nivel', 'professor')),

            'aluno' => Tab::make('Alunos')
                ->icon('heroicon-o-user-group')
                ->badge(fn () => \App\Models\User::where('nivel', 'aluno')->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('nivel', 'aluno')),

            'ativos' => Tab::make('Ativos')
                ->icon('heroicon-o-check-circle')
                ->badge(fn () => \App\Models\User::where('ativo', true)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('ativo', true)),

            'inativos' => Tab::make('Inativos')
                ->icon('heroicon-o-x-circle')
                ->badge(fn () => \App\Models\User::where('ativo', false)->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('ativo', false)),
        ];
    }
}
