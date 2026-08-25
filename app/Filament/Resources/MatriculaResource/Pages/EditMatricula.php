<?php

namespace App\Filament\Resources\MatriculaResource\Pages;

use App\Filament\Resources\MatriculaResource;
use Filament\Resources\Pages\EditRecord;

class EditMatricula extends EditRecord
{
    protected static string $resource = MatriculaResource::class;

    /** @param array<string, mixed> $data */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['status'] = MatriculaResource::normalizarStatus($data['status'] ?? null);

        return $data;
    }

    /** @param array<string, mixed> $data */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return MatriculaResource::prepararDadosAcademicos($data, $this->record);
    }
}
