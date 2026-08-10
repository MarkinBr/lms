<?php

namespace App\Filament\Resources\MensalidadeResource\Pages;

use App\Filament\Resources\MensalidadeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMensalidade extends EditRecord
{
    protected static string $resource = MensalidadeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
