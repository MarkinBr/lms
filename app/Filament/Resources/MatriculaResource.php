<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MatriculaResource\Pages;
use App\Filament\Resources\MatriculaResource\RelationManagers;
use App\Models\Matricula;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MatriculaResource extends Resource
{
    protected static ?string $model = Matricula::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Nova Matrícula')
                    ->schema([
                        // Busca os alunos pelo nome
                        Forms\Components\Select::make('aluno_id')
                            ->relationship('aluno', 'nome') 
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Aluno'),

                        // Busca as turmas pelo nome
                        Forms\Components\Select::make('turma_id')
                            ->relationship('turma', 'nome')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Turma'),

                        Forms\Components\DatePicker::make('data_matricula')
                            ->default(now())
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->options([
                                'Ativa' => 'Ativa',
                                'Trancada' => 'Trancada',
                                'Concluída' => 'Concluída',
                                'Cancelada' => 'Cancelada',
                            ])
                            ->default('Ativa')
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('aluno.nome') // Pega o nome na tabela de alunos
                    ->label('Aluno')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('turma.nome')
                    ->label('Turma')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge() // Cria uma etiqueta colorida automática
                    ->color(fn (string $state): string => match ($state) {
                        'Ativa' => 'success', // Verde
                        'Trancada' => 'warning', // Amarelo
                        'Cancelada' => 'danger', // Vermelho
                        'Concluída' => 'info', // Azul
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('data_matricula')
                    ->date('d/m/Y')
                    ->label('Data'),
            ])
            ->filters([
                // Filtro para achar só matrículas ativas, se quiser depois
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMatriculas::route('/'),
            'create' => Pages\CreateMatricula::route('/create'),
            'edit' => Pages\EditMatricula::route('/{record}/edit'),
        ];
    }
}
