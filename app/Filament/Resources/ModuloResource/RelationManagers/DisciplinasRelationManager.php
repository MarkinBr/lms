<?php

namespace App\Filament\Resources\ModuloResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Disciplinas de um módulo (Issue #57).
 *
 * O curso da disciplina é derivado no servidor a partir do módulo dono —
 * o usuário nunca escolhe um curso incompatível. A validação final de
 * integridade permanece no model Disciplina.
 */
class DisciplinasRelationManager extends RelationManager
{
    protected static string $relationship = 'disciplinas';

    protected static ?string $title = 'Disciplinas';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('codigo')
                    ->label('Código')
                    ->required()
                    ->maxLength(50),

                Forms\Components\TextInput::make('nome')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Textarea::make('descricao')
                    ->label('Descrição')
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('carga_horaria')
                    ->label('Carga horária (h)')
                    ->numeric()
                    ->required()
                    ->minValue(1),

                Forms\Components\TextInput::make('ordem')
                    ->label('Ordem')
                    ->numeric()
                    ->default(1)
                    ->required()
                    ->minValue(1),

                Forms\Components\Toggle::make('ativa')
                    ->label('Ativa')
                    ->default(true),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nome')
            ->defaultSort('ordem')
            ->columns([
                Tables\Columns\TextColumn::make('ordem')
                    ->label('#')
                    ->sortable()
                    ->width(50),

                Tables\Columns\TextColumn::make('codigo')
                    ->label('Código'),

                Tables\Columns\TextColumn::make('nome')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('aulas_count')
                    ->counts('aulas')
                    ->label('Aulas')
                    ->alignCenter()
                    ->badge()
                    ->color('success'),

                Tables\Columns\IconColumn::make('ativa')
                    ->label('Ativa')
                    ->boolean(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(fn (array $data): array => [
                        ...$data,
                        'curso_id' => $this->getOwnerRecord()->curso_id,
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(fn (array $data): array => [
                        ...$data,
                        'curso_id' => $this->getOwnerRecord()->curso_id,
                    ]),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([]);
    }
}
