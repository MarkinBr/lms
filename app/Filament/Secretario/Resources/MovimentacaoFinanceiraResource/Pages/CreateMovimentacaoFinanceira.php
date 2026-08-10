<?php

// ==========================================
// CreateMovimentacaoFinanceira.php
// ==========================================

namespace App\Filament\Secretario\Resources\MovimentacaoFinanceiraResource\Pages;

use App\Filament\Secretario\Resources\MovimentacaoFinanceiraResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMovimentacaoFinanceira extends CreateRecord
{
    protected static string $resource = MovimentacaoFinanceiraResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['criado_por'] = auth()->id();
        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->registrarAuditoria('criacao');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
