<?php

namespace App\Filament\Resources\PaginaInicialResource\Pages;

use App\Filament\Resources\PaginaInicialResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPaginaInicial extends EditRecord
{
    protected static string $resource = PaginaInicialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
