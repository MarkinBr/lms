<?php

namespace App\Filament\Resources\ModuloResource\Pages;

use App\Filament\Resources\ModuloResource;
use Filament\Resources\Pages\CreateRecord;

class CreateModulo extends CreateRecord
{
    protected static string $resource = ModuloResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Módulo criado com sucesso!';
    }
}
