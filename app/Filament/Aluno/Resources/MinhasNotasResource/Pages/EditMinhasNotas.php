<?php

namespace App\Filament\Aluno\Resources\MinhasNotasResource\Pages;

use App\Filament\Aluno\Resources\MinhasNotasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMinhasNotas extends EditRecord
{
    protected static string $resource = MinhasNotasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
