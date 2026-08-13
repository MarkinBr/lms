<?php

namespace App\Filament\Secretario\Resources\DisciplinaSecretariaResource\Pages;

use App\Filament\Secretario\Resources\DisciplinaSecretariaResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageDisciplinasSecretaria extends ManageRecords
{
    protected static string $resource = DisciplinaSecretariaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
