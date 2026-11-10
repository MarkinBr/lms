<?php

namespace App\Filament\Secretario\Resources\ChamadaFrequenciaResource\Pages;

use App\Filament\Secretario\Resources\ChamadaFrequenciaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChamadasFrequencia extends ListRecords
{
    protected static string $resource = ChamadaFrequenciaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
