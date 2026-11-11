<?php

namespace App\Filament\Resources\AcervoDidaticoResource\Pages;

use App\Filament\Resources\AcervoDidaticoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAcervoDidatico extends EditRecord
{
    protected static string $resource = AcervoDidaticoResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
