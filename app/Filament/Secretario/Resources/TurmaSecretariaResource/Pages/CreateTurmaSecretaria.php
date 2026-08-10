<?php
// ==========================================
// CreateTurmaSecretaria.php
// ==========================================

namespace App\Filament\Secretario\Resources\TurmaSecretariaResource\Pages;

use App\Filament\Secretario\Resources\TurmaSecretariaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTurmaSecretaria extends CreateRecord
{
    protected static string $resource = TurmaSecretariaResource::class;

    // Redirecionar para a lista após criar
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
