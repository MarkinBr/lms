<?php

namespace App\Filament\Resources\DisciplinaResource\Pages;

use App\Filament\Resources\DisciplinaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDisciplina extends EditRecord
{
    protected static string $resource = DisciplinaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Sem ação de excluir aqui: a proteção de histórico fica no model
            // (bloqueia exclusão com aulas/turmas) e na DisciplinaPolicy.
            Actions\DeleteAction::make()
                ->visible(fn ($record) => $record->podeSerExcluida() && ! $record->aulas()->exists()),
        ];
    }
}
