<?php

namespace App\Filament\Secretario\Resources\TurmaSecretariaResource\RelationManagers;

use App\Models\PeriodoLetivo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PeriodosTurmaRelationManager extends RelationManager
{
    protected static string $relationship = 'turmaPeriodos';

    protected static ?string $title = 'Períodos letivos da turma';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('periodo_letivo_id')
                ->label('Período letivo')
                ->options(PeriodoLetivo::query()->orderByDesc('data_inicio')->pluck('nome', 'id'))
                ->required()->searchable()->preload(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('periodoLetivo.nome')->label('Período')->sortable(),
            Tables\Columns\TextColumn::make('periodoLetivo.data_inicio')->label('Início')->date('d/m/Y'),
            Tables\Columns\TextColumn::make('periodoLetivo.data_fim')->label('Fim')->date('d/m/Y'),
        ])
            ->headerActions([Tables\Actions\CreateAction::make()->label('Vincular período')])
            ->actions([Tables\Actions\DeleteAction::make()->label('Desvincular')
                ->visible(fn ($record): bool => (int) $record->periodo_letivo_id !== (int) $this->getOwnerRecord()->periodo_letivo_id
                    && ! $this->getOwnerRecord()->turmaDisciplinas()
                        ->where('periodo_letivo_id', $record->periodo_letivo_id)->exists())])
            ->bulkActions([]);
    }
}
