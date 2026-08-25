<?php

namespace App\Filament\Resources\DisciplinaResource\Pages;

use App\Filament\Concerns\UsesGestaoAcademicaLayout;
use App\Filament\Resources\DisciplinaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDisciplina extends CreateRecord
{
    use UsesGestaoAcademicaLayout;

    protected static string $resource = DisciplinaResource::class;
}
