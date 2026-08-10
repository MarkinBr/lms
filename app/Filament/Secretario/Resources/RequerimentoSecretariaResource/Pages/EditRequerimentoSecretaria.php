<?php

// ==========================================
// EditRequerimentoSecretaria.php
// ==========================================

namespace App\Filament\Secretario\Resources\RequerimentoSecretariaResource\Pages;

use App\Filament\Secretario\Resources\RequerimentoSecretariaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRequerimentoSecretaria extends EditRecord
{
    protected static string $resource = RequerimentoSecretariaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Se o status mudou para resolvido ou negado, registra quem respondeu
        if (in_array($data['status'], ['resolvido', 'negado'])) {
            if (!$this->record->respondido_por) {
                $data['respondido_por'] = auth()->id();
                $data['respondido_em'] = now();
            }
        }
        
        return $data;
    }
}