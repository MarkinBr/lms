<?php

namespace App\Filament\Resources\CursoResource\Pages;

use App\Filament\Resources\CursoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCurso extends CreateRecord
{
    protected static string $resource = CursoResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Curso criado com sucesso!';
    }
}
