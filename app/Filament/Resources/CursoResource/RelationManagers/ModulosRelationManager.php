<?php

namespace App\Filament\Resources\CursoResource\RelationManagers;

use App\Filament\Resources\ModuloResource;
use App\Models\Modulo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ModulosRelationManager extends RelationManager
{
    protected static string $relationship = 'modulos';

    protected static ?string $title = 'Módulos do Curso';

    protected static ?string $recordTitleAttribute = 'titulo';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('titulo')
                    ->label('Título do Módulo')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('descricao')
                    ->label('Descrição')
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('ordem')
                    ->label('Ordem de Exibição')
                    ->numeric()
                    ->default(fn () => $this->getOwnerRecord()->modulos()->max('ordem') + 1)
                    ->required()
                    ->minValue(1),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('titulo')
            ->columns([
                Tables\Columns\TextColumn::make('ordem')
                    ->label('#')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('titulo')
                    ->label('Título')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->descricao),

                Tables\Columns\TextColumn::make('disciplinas_count')
                    ->counts('disciplinas')
                    ->label('Disciplinas')
                    ->alignCenter()
                    ->badge(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Novo Módulo')
                    ->icon('heroicon-o-plus'),
            ])
            ->actions([
                Tables\Actions\Action::make('gerenciar_disciplinas')
                    ->label('Disciplinas')
                    ->icon('heroicon-o-book-open')
                    ->url(fn (Modulo $record): string => ModuloResource::getUrl('edit', ['record' => $record])),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Modulo $record): bool => $record->podeSerExcluido()),
            ])
            ->bulkActions([])
            ->defaultSort('ordem')
            ->reorderable('ordem')
            ->emptyStateHeading('Nenhum módulo cadastrado')
            ->emptyStateDescription('Comece criando o primeiro módulo deste curso')
            ->emptyStateIcon('heroicon-o-book-open');
    }
}
