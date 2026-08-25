<?php

namespace App\Filament\Resources\MatriculaResource\Pages;

use App\Filament\Concerns\UsesGestaoAcademicaLayout;
use App\Filament\Resources\MatriculaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMatricula extends CreateRecord
{
    use UsesGestaoAcademicaLayout;

    protected static string $resource = MatriculaResource::class;

    /** @param array<string, mixed> $data */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return MatriculaResource::prepararDadosAcademicos($data);
    }
}
