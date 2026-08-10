<?php
// ==========================================
// ListTurmasSecretaria.php
// ==========================================
namespace App\Filament\Secretario\Resources\TurmaSecretariaResource\Pages;

use App\Filament\Secretario\Resources\TurmaSecretariaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTurmasSecretaria extends ListRecords
{
    protected static string $resource = TurmaSecretariaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nova Turma')
                ->icon('heroicon-o-plus'),
        ];
    }
}