<?php

namespace App\Filament\Professor\Resources\CursoResource\Pages;

use App\Filament\Professor\Resources\CursoResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateCurso extends CreateRecord
{
    protected static string $resource = CursoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['instrutor_id'] = auth()->id();
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Curso criado!')
            ->body('Agora adicione módulos e aulas ao seu curso.');
    }
}
