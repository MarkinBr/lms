<?php

namespace App\Filament\Resources\ModuloResource\Pages;

use App\Filament\Concerns\UsesGestaoAcademicaLayout;
use App\Filament\Resources\ModuloResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditModulo extends EditRecord
{
    use UsesGestaoAcademicaLayout;

    protected static string $resource = ModuloResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn (): bool => $this->getRecord()->podeSerExcluido()),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Módulo atualizado com sucesso!';
    }
}
