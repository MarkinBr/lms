<?php

// ==========================================
// EditTurmaSecretaria.php
// ==========================================

namespace App\Filament\Secretario\Resources\TurmaSecretariaResource\Pages;

use App\Filament\Secretario\Resources\TurmaSecretariaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTurmaSecretaria extends EditRecord
{
    protected static string $resource = TurmaSecretariaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    // Redirecionar para a lista após editar
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}