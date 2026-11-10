<?php

namespace App\Filament\Professor\Resources\AulaAoVivoProfessorResource\Pages;

use App\Filament\Professor\Resources\AulaAoVivoProfessorResource;
use Filament\Resources\Pages\ListRecords;

class ListAulasAoVivoProfessor extends ListRecords
{
    protected static string $resource = AulaAoVivoProfessorResource::class;

    protected function getHeaderActions(): array
    {
        // Somente-leitura: nenhuma ação de criação disponível para o professor.
        return [];
    }
}
