<?php

namespace App\Filament\Professor\Resources\AulaResource\Pages;

use App\Filament\Professor\Resources\AulaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAulas extends ListRecords
{
    protected static string $resource = AulaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nova Aula')
                ->icon('heroicon-o-plus-circle'),
        ];
    }

    // Permite filtrar por modulo_id via query string
    protected function getTableQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getTableQuery();

        if ($moduloId = request()->query('modulo_id')) {
            $query->where('modulo_id', $moduloId);
        }

        return $query;
    }
}
