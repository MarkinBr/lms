<?php

namespace App\Filament\Resources\PessoaResource\Pages;

use App\Filament\Concerns\UsesGestaoAcademicaLayout;
use App\Filament\Resources\PessoaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPessoa extends EditRecord
{
    use UsesGestaoAcademicaLayout;

    protected static string $resource = PessoaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
