<?php

namespace App\Filament\Resources\AulaAoVivoResource\Pages;

use App\Filament\Resources\AulaAoVivoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAulaAoVivo extends EditRecord
{
    protected static string $resource = AulaAoVivoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Aula ao vivo atualizada com sucesso!';
    }
}
