<?php

namespace App\Filament\Professor\Resources\CursoResource\Pages;

use App\Filament\Professor\Resources\CursoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCursos extends ListRecords
{
    protected static string $resource = CursoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Novo Curso')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
