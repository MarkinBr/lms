<?php

namespace App\Filament\Resources\MensalidadeResource\Pages;

use App\Filament\Resources\MensalidadeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Concerns\ExposesTableToWidgets; // <--- Importante

class ListMensalidades extends ListRecords
{
    // Adicione esta linha para o Widget saber o que você filtrou
    use ExposesTableToWidgets; 

    protected static string $resource = MensalidadeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    // Registra o widget no topo da página
    protected function getHeaderWidgets(): array
    {
        return [
            MensalidadeResource\Widgets\MensalidadeStats::class,
        ];
    }
}