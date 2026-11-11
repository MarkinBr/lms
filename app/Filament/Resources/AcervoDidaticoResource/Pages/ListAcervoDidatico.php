<?php

namespace App\Filament\Resources\AcervoDidaticoResource\Pages;

use App\Filament\Resources\AcervoDidaticoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAcervoDidatico extends ListRecords
{
    protected static string $resource = AcervoDidaticoResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
