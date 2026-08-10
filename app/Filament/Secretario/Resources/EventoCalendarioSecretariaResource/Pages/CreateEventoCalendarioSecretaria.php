<?php

namespace App\Filament\Secretario\Resources\EventoCalendarioSecretariaResource\Pages;

use App\Filament\Secretario\Resources\EventoCalendarioSecretariaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEventoCalendarioSecretaria extends CreateRecord
{
    protected static string $resource = EventoCalendarioSecretariaResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
