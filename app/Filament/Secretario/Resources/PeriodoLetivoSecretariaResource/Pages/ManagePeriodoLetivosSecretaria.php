<?php

namespace App\Filament\Secretario\Resources\PeriodoLetivoSecretariaResource\Pages;

use App\Filament\Secretario\Resources\PeriodoLetivoSecretariaResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePeriodoLetivosSecretaria extends ManageRecords
{
    protected static string $resource = PeriodoLetivoSecretariaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
