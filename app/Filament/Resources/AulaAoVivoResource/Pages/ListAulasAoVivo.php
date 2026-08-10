<?php

namespace App\Filament\Resources\AulaAoVivoResource\Pages;

use App\Filament\Resources\AulaAoVivoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAulasAoVivo extends ListRecords
{
    protected static string $resource = AulaAoVivoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nova Aula ao Vivo')
                ->icon('heroicon-o-plus'),
        ];
    }
}
