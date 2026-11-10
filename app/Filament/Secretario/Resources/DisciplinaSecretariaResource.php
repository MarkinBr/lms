<?php

namespace App\Filament\Secretario\Resources;

use App\Filament\Secretario\Resources\DisciplinaSecretariaResource\Pages;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Modulo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DisciplinaSecretariaResource extends Resource
{
    protected static ?string $model = Disciplina::class;

    protected static ?string $modelLabel = 'Disciplina';

    protected static ?string $pluralModelLabel = 'Disciplinas';

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = '📚 Acadêmico';

    protected static ?int $navigationSort = 4;

    public static function usuarioPodeGerenciar(): bool
    {
        $user = auth()->user();

        return $user && $user->ativo && in_array($user->nivel, ['admin', 'secretario'], true);
    }

    public static function canAccess(): bool
    {
        return static::usuarioPodeGerenciar();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Disciplina')
                    ->schema([
                        Forms\Components\Select::make('curso_id')
                            ->label('Curso')
                            ->options(Curso::where('ativo', true)->pluck('titulo', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get) {
                                // Se o curso muda e o módulo atual não pertence
                                // mais ao novo curso, limpa a seleção para
                                // evitar associação curso/módulo inválida.
                                $moduloId = $get('modulo_id');

                                if (! $moduloId) {
                                    return;
                                }

                                $pertence = Modulo::query()
                                    ->whereKey($moduloId)
                                    ->where('curso_id', $get('curso_id'))
                                    ->exists();

                                if (! $pertence) {
                                    $set('modulo_id', null);
                                }
                            })
                            ->columnSpanFull(),
                        Forms\Components\Select::make('modulo_id')
                            ->label('Módulo')
                            ->options(function (Get $get) {
                                $cursoId = $get('curso_id');

                                if (! $cursoId) {
                                    return [];
                                }

                                return Modulo::query()
                                    ->where('curso_id', $cursoId)
                                    ->orderBy('ordem')
                                    ->pluck('titulo', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Obrigatório. Só lista módulos do curso selecionado.')
                            ->disabled(fn (Get $get) => ! $get('curso_id'))
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('codigo')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('nome')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('descricao')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('carga_horaria')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        Forms\Components\TextInput::make('ordem')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        Forms\Components\Toggle::make('ativa')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('codigo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nome')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('curso.titulo')
                    ->label('Curso')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                Tables\Columns\TextColumn::make('modulo.titulo')
                    ->label('Módulo')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('carga_horaria')
                    ->label('C/H')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ordem')
                    ->sortable(),
                Tables\Columns\IconColumn::make('ativa')
                    ->boolean(),
                Tables\Columns\TextColumn::make('turmas_count')
                    ->counts('turmas')
                    ->label('Turmas')
                    ->badge()
                    ->color('info'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('curso_id')
                    ->label('Curso')
                    ->relationship('curso', 'titulo')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('modulo_id')
                    ->label('Módulo')
                    ->relationship('modulo', 'titulo')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('ativa')
                    ->label('Somente ativas'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Disciplina $record): bool => $record->podeSerExcluida()),
            ])
            ->bulkActions([])
            ->defaultSort('ordem');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageDisciplinasSecretaria::route('/'),
        ];
    }
}
