<?php

namespace App\Filament\Resources\PeriodoLetivoResource\Pages;

use App\Filament\Concerns\UsesGestaoAcademicaLayout;
use App\Filament\Resources\PeriodoLetivoResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePeriodoLetivos extends ManageRecords
{
    use UsesGestaoAcademicaLayout;

    protected static string $resource = PeriodoLetivoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
