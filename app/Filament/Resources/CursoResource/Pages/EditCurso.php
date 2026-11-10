<?php

namespace App\Filament\Resources\CursoResource\Pages;

use App\Filament\Concerns\UsesGestaoAcademicaLayout;
use App\Filament\Resources\CursoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCurso extends EditRecord
{
    use UsesGestaoAcademicaLayout;

    protected static string $resource = CursoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
            Actions\ForceDeleteAction::make(),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Curso atualizado com sucesso!';
    }
}
