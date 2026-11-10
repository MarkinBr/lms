<?php

namespace App\Filament\Secretario\Resources\AulaAoVivoSecretariaResource\Pages;

use App\Filament\Secretario\Resources\AulaAoVivoSecretariaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAulasAoVivoSecretaria extends ListRecords
{
    protected static string $resource = AulaAoVivoSecretariaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
