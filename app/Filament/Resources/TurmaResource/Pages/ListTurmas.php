<?php

namespace App\Filament\Resources\TurmaResource\Pages;

use App\Filament\Concerns\UsesGestaoAcademicaLayout;
use App\Filament\Resources\TurmaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTurmas extends ListRecords
{
    use UsesGestaoAcademicaLayout;

    protected static string $resource = TurmaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nova Turma')
                ->icon('heroicon-o-plus'),
        ];
    }
}
