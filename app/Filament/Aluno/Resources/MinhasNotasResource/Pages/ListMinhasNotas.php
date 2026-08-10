<?php

namespace App\Filament\Aluno\Resources\MinhasNotasResource\Pages;

use App\Filament\Aluno\Resources\MinhasNotasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMinhasNotas extends ListRecords
{
    protected static string $resource = MinhasNotasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
