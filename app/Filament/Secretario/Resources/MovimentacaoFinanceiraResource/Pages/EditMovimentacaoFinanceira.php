<?php
// ==========================================
// EditMovimentacaoFinanceira.php
// ==========================================

namespace App\Filament\Secretario\Resources\MovimentacaoFinanceiraResource\Pages;

use App\Filament\Secretario\Resources\MovimentacaoFinanceiraResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMovimentacaoFinanceira extends EditRecord
{
    protected static string $resource = MovimentacaoFinanceiraResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['alterado_por'] = auth()->id();
        $data['data_alteracao'] = now();
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}