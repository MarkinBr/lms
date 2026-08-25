<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DisciplinaResource\Pages;
use App\Filament\Resources\DisciplinaResource\RelationManagers;
use App\Models\Disciplina;
use App\Models\Modulo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Resource administrativo de Disciplinas (Issue #57).
 *
 * Rota ativa no painel /admin, mas fora do menu: a entrada visível é a
 * central Gestão Acadêmica. Resource independente — não herda do Resource
 * da Secretaria; a validação de integridade vive no model Disciplina.
 */
class DisciplinaResource extends Resource
{
    protected static ?string $model = Disciplina::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Disciplinas';

    protected static ?string $modelLabel = 'Disciplina';

    protected static ?string $pluralModelLabel = 'Disciplinas';

    protected static ?string $navigationGroup = 'Acadêmico';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Select::make('curso_id')
                            ->label('Curso')
                            ->relationship('curso', 'titulo')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('modulo_id', null)),

                        Forms\Components\Select::make('modulo_id')
                            ->label('Módulo')
                            ->options(fn (Forms\Get $get) => filled($get('curso_id'))
                                ? Modulo::query()
                                    ->where('curso_id', $get('curso_id'))
                                    ->orderBy('ordem')
                                    ->pluck('titulo', 'id')
                                : [])
                            ->searchable()
                            ->required()
                            ->helperText('Somente módulos do curso selecionado.'),

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
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('curso.titulo')
                    ->label('Curso')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('modulo.titulo')
                    ->label('Módulo')
                    ->placeholder('— sem módulo —')
                    ->sortable(),

                Tables\Columns\TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable(),

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
            ->filters([
                Tables\Filters\SelectFilter::make('curso_id')
                    ->label('Curso')
                    ->relationship('curso', 'titulo')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('modulo_id')
                    ->label('Vínculo com módulo')
                    ->placeholder('Todas')
                    ->trueLabel('Sem módulo')
                    ->falseLabel('Com módulo')
                    ->queries(
                        true: fn ($query) => $query->whereNull('modulo_id'),
                        false: fn ($query) => $query->whereNotNull('modulo_id'),
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AulasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDisciplinas::route('/'),
            'create' => Pages\CreateDisciplina::route('/create'),
            'edit' => Pages\EditDisciplina::route('/{record}/edit'),
        ];
    }
}
