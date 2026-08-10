<?php

namespace App\Filament\Professor\Resources\AulaResource\Pages;

use App\Filament\Professor\Resources\AulaResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateAula extends CreateRecord
{
    protected static string $resource = AulaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Calcular quantidade de questões
        if (isset($data['questoes'])) {
            $data['questoes_quantidade'] = count($data['questoes']);
        }

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
            ->title('Aula criada com sucesso!')
            ->body('Continue editando para adicionar mais conteúdo.');
    }
}
