<?php

namespace App\Filament\Secretario\Resources\CadastroRapidoResource\Pages;

use App\Filament\Secretario\Resources\CadastroRapidoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCadastroRapido extends ListRecords
{
    protected static string $resource = CadastroRapidoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('⚡ Novo Cadastro Rápido')
                ->icon('heroicon-o-bolt')
                ->color('success')
                ->modalHeading('⚡ Cadastro Completo em 6 Etapas')
                ->modalDescription('Preencha os dados do aluno seguindo as etapas. Você pode pular etapas e completar depois.')
                ->modalWidth('7xl')
                ->slideOver(false), // Usar modal completo ao invés de slideOver
        ];
    }
}