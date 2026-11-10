<?php

namespace App\Filament\Secretario\Resources\ChamadaFrequenciaResource\Pages;

use App\Filament\Secretario\Resources\ChamadaFrequenciaResource;
use App\Models\ChamadaFrequencia;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChamadaFrequencia extends EditRecord
{
    protected static string $resource = ChamadaFrequenciaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Preserva o criador original; a última gravação fica registrada
        // em concluida_por (ao concluir) e em frequencias.registrado_por.
        unset($data['criada_por']);

        if (($data['status'] ?? null) === ChamadaFrequencia::STATUS_CONCLUIDA) {
            $data['concluida_por'] = auth()->id();
        }

        return $data;
    }
}
