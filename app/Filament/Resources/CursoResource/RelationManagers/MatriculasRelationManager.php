<?php

namespace App\Filament\Resources\CursoResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\User;

class MatriculasRelationManager extends RelationManager
{
    protected static string $relationship = 'matriculas';

    protected static ?string $title = 'Matrículas do Curso';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('aluno_id')
                    ->label('Aluno')
                    ->relationship('aluno', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\Select::make('turma_id')
                    ->label('Turma')
                    ->relationship('turma', 'nome')
                    ->searchable()
                    ->nullable(),

                Forms\Components\DatePicker::make('data_matricula')
                    ->label('Data da Matrícula')
                    ->default(now())
                    ->required(),

                Forms\Components\DatePicker::make('data_validade')
                    ->label('Data de Validade')
                    ->nullable(),

                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'ativo' => 'Ativo',
                        'concluido' => 'Concluído',
                        'cancelado' => 'Cancelado',
                        'trancado' => 'Trancado',
                    ])
                    ->default('ativo')
                    ->required(),

                Forms\Components\Textarea::make('observacoes')
                    ->label('Observações')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('aluno.name')
                    ->label('Aluno')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->aluno->email ?? ''),

                Tables\Columns\TextColumn::make('turma.nome')
                    ->label('Turma')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Sem turma'),

                Tables\Columns\TextColumn::make('data_matricula')
                    ->label('Data Matrícula')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('data_validade')
                    ->label('Validade')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('Indeterminada'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ativo' => 'success',
                        'concluido' => 'info',
                        'cancelado' => 'danger',
                        'trancado' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'ativo' => 'Ativo',
                        'concluido' => 'Concluído',
                        'cancelado' => 'Cancelado',
                        'trancado' => 'Trancado',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nova Matrícula')
                    ->icon('heroicon-o-plus'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Nenhuma matrícula encontrada')
            ->emptyStateDescription('Cadastre a primeira matrícula deste curso')
            ->emptyStateIcon('heroicon-o-user-group');
    }
}
