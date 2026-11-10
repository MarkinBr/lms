<?php

namespace App\Filament\Resources\CursoResource\Pages;

use App\Filament\Concerns\UsesGestaoAcademicaLayout;
use App\Filament\Resources\CursoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCursos extends ListRecords
{
    use UsesGestaoAcademicaLayout;

    protected static string $resource = CursoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Novo Curso')
                ->icon('heroicon-o-plus'),
        ];
    }
}
