<?php

namespace App\Filament\Resources\TurmaResource\Pages;

use App\Filament\Concerns\UsesGestaoAcademicaLayout;
use App\Filament\Resources\TurmaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTurma extends CreateRecord
{
    use UsesGestaoAcademicaLayout;

    protected static string $resource = TurmaResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
