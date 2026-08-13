<?php

namespace App\Filament\Secretario\Resources\ChamadaFrequenciaResource\Pages;

use App\Filament\Secretario\Resources\ChamadaFrequenciaResource;
use App\Models\ChamadaFrequencia;
use Filament\Resources\Pages\CreateRecord;

class CreateChamadaFrequencia extends CreateRecord
{
    protected static string $resource = ChamadaFrequenciaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['criada_por'] = auth()->id();

        if (($data['status'] ?? null) === ChamadaFrequencia::STATUS_CONCLUIDA) {
            $data['concluida_por'] = auth()->id();
        }

        return $data;
    }
}
