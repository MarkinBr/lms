<?php

namespace App\Filament\Secretario\Resources\TurmaSecretariaResource\RelationManagers;

use App\Models\Disciplina;
use App\Models\PeriodoLetivo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DisciplinasTurmaRelationManager extends RelationManager
{
    protected static string $relationship = 'turmaDisciplinas';

    protected static ?string $title = 'Disciplinas oferecidas na turma';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('disciplina_id')
                ->label('Disciplina')
                ->options(fn () => Disciplina::query()
                    ->where('curso_id', $this->getOwnerRecord()->curso_id)
                    ->where('ativa', true)->orderBy('ordem')->pluck('nome', 'id'))
                ->searchable()->preload()->required(),
            Forms\Components\Select::make('periodo_letivo_id')
                ->label('Período em que a disciplina será oferecida (opcional)')
                ->options(fn () => PeriodoLetivo::query()->whereIn('id',
                    $this->getOwnerRecord()->periodos()->pluck('periodo_letivos.id'))
                    ->orderByDesc('data_inicio')->pluck('nome', 'id'))
                ->nullable()->searchable()->preload(),
            Forms\Components\TextInput::make('ordem')
                ->label('Ordem')->numeric()->minValue(1)->required()->default(1),
            Forms\Components\Toggle::make('ativa')->label('Em oferta')->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('disciplina.codigo')->label('Código')->searchable(),
            Tables\Columns\TextColumn::make('disciplina.nome')->label('Disciplina')->searchable(),
            Tables\Columns\TextColumn::make('periodoLetivo.nome')->label('Período')->placeholder('A definir'),
            Tables\Columns\TextColumn::make('ordem')->label('Ordem')->sortable(),
            Tables\Columns\IconColumn::make('ativa')->label('Ativa')->boolean(),
        ])
            ->headerActions([Tables\Actions\CreateAction::make()->label('Adicionar disciplina')])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()->label('Desvincular')
                    ->visible(fn ($record): bool => (int) $record->disciplina_id !== (int) $this->getOwnerRecord()->disciplina_id
                        && ! $this->getOwnerRecord()->professorAtribuicoes()
                            ->where('disciplina_id', $record->disciplina_id)->exists()),
            ])
            ->bulkActions([]);
    }
}
