<?php

namespace App\Filament\Professor\Resources\CursoResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ModulosRelationManager extends RelationManager
{
    protected static string $relationship = 'modulos';

    protected static ?string $title = 'Módulos e estrutura curricular';

    protected static ?string $modelLabel = 'Módulo';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações do Módulo')
                    ->icon('heroicon-o-folder')
                    ->schema([
                        Forms\Components\TextInput::make('titulo')
                            ->label('Título do Módulo')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->placeholder('Ex: Módulo 1 - Princípios Administrativos'),

                        Forms\Components\Textarea::make('descricao')
                            ->label('Descrição')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Descrição breve do conteúdo deste módulo...'),

                        Forms\Components\TextInput::make('ordem')
                            ->label('Ordem de Exibição')
                            ->numeric()
                            ->default(fn () => $this->ownerRecord->modulos()->count() + 1)
                            ->required()
                            ->minValue(1),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('titulo')
            ->reorderable('ordem')
            ->defaultSort('ordem')
            ->columns([
                Tables\Columns\TextColumn::make('ordem')
                    ->label('#')
                    ->sortable()
                    ->alignCenter()
                    ->width(50)
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('titulo')
                    ->label('Módulo')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => Str::limit($record->descricao, 80)),

                Tables\Columns\TextColumn::make('disciplinas_count')
                    ->counts('disciplinas')
                    ->label('Disciplinas')
                    ->alignCenter()
                    ->badge()
                    ->color('success')
                    ->suffix(' disciplinas'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Novo Módulo')
                    ->icon('heroicon-o-folder-plus')
                    ->modalHeading('Criar Novo Módulo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar')
                    ->modalHeading('Editar Módulo'),

                Tables\Actions\DeleteAction::make()
                    ->label('Excluir')
                    ->modalHeading('Excluir Módulo')
                    ->modalDescription('Só é possível excluir módulos sem disciplinas e sem aulas.')
                    ->visible(fn ($record): bool => $record->podeSerExcluido()),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Nenhum módulo criado')
            ->emptyStateDescription('Adicione o primeiro módulo ao seu curso')
            ->emptyStateIcon('heroicon-o-folder-open');
    }
}
