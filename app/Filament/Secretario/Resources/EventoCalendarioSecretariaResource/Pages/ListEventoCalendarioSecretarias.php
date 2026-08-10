<?php

namespace App\Filament\Secretario\Resources\EventoCalendarioSecretariaResource\Pages;

use App\Filament\Secretario\Resources\EventoCalendarioSecretariaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEventoCalendarioSecretarias extends ListRecords
{
    protected static string $resource = EventoCalendarioSecretariaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Novo evento')
                ->icon('heroicon-o-plus'),
        ];
    }
}
