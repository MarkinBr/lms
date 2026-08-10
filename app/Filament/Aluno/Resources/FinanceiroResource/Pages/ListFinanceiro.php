<?php

namespace App\Filament\Aluno\Resources\FinanceiroResource\Pages;

use App\Filament\Aluno\Resources\FinanceiroResource;
use Filament\Resources\Pages\ListRecords;

class ListFinanceiro extends ListRecords // <--- Mudou de ListFinanceiros para ListFinanceiro
{
    protected static string $resource = FinanceiroResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            FinanceiroResource\Widgets\ResumoFinanceiroWidget::class,
        ];
    }
}