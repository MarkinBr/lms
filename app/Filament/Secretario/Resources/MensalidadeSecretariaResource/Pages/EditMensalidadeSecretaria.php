<?php

// ==========================================
// EditMensalidadeSecretaria.php
// ==========================================

namespace App\Filament\Secretario\Resources\MensalidadeSecretariaResource\Pages;

use App\Filament\Secretario\Resources\MensalidadeSecretariaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMensalidadeSecretaria extends EditRecord
{
    protected static string $resource = MensalidadeSecretariaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('gerar_recibo')
                ->label('Gerar Recibo')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->visible(fn () => $this->record->status === 'Pago')
                ->url(fn () => route('mensalidade.recibo.pdf', $this->record->id))
                ->openUrlInNewTab(),

            Actions\DeleteAction::make(),
        ];
    }
}