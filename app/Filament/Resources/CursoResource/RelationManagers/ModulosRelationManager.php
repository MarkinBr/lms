<?php

namespace App\Filament\Resources\CursoResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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

                Tables\Columns\TextColumn::make('aulas_count')
                    ->counts('aulas')
                    ->label('Aulas')
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
                Tables\Actions\Action::make('gerenciar_aulas')
                    ->label('Aulas')
                    ->icon('heroicon-o-play')
                    ->url(fn ($record) => route('filament.admin.resources.modulos.edit', ['record' => $record->id]))
                    ->openUrlInNewTab(),
                    
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('ordem')
            ->reorderable('ordem')
            ->emptyStateHeading('Nenhum módulo cadastrado')
            ->emptyStateDescription('Comece criando o primeiro módulo deste curso')
            ->emptyStateIcon('heroicon-o-book-open');
    }
}
