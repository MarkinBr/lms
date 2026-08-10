<?php

namespace App\Filament\Secretario\Resources\EventoCalendarioSecretariaResource\Pages;

use App\Filament\Secretario\Resources\EventoCalendarioSecretariaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEventoCalendarioSecretaria extends EditRecord
{
    protected static string $resource = EventoCalendarioSecretariaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
