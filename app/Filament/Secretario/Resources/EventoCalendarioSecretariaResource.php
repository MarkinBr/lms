<?php

namespace App\Filament\Secretario\Resources;

use App\Filament\Secretario\Resources\EventoCalendarioSecretariaResource\Pages;
use App\Models\Curso;
use App\Models\EventoCalendario;
use App\Models\Turma;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EventoCalendarioSecretariaResource extends Resource
{
    protected static ?string $model = EventoCalendario::class;
    protected static ?string $modelLabel = 'Evento do calendário';
    protected static ?string $pluralModelLabel = 'Eventos do calendário';
    protected static ?string $navigationLabel = 'Agenda Acadêmica';
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Acadêmico';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Vínculo do evento')
                    ->description('Defina se o evento é geral, por curso ou específico de uma turma.')
                    ->icon('heroicon-o-link')
                    ->schema([
                        Forms\Components\Select::make('curso_id')
                            ->label('Curso')
                            ->options(Curso::query()->orderBy('titulo')->pluck('titulo', 'id'))
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->live(),

                        Forms\Components\Select::make('turma_id')
                            ->label('Turma')
                            ->options(fn (Forms\Get $get) => Turma::query()
                                ->when($get('curso_id'), fn ($query, $cursoId) => $query->where('curso_id', $cursoId))
                                ->orderBy('nome')
                                ->pluck('nome', 'id'))
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Evento geral ou selecione uma turma')
                            ->helperText('Se deixar curso e turma em branco, o evento será geral para todos os alunos.'),
                    ])->columns(2),

                Forms\Components\Section::make('Detalhes do evento')
                    ->icon('heroicon-o-pencil-square')
                    ->schema([
                        Forms\Components\TextInput::make('titulo')
                            ->label('Título')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('descricao')
                            ->label('Descrição')
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('tipo')
                            ->label('Tipo')
                            ->options([
                                'academico' => 'Acadêmico',
                                'secretaria' => 'Secretaria',
                                'prazo' => 'Prazo importante',
                                'prova' => 'Prova / avaliação',
                                'encontro' => 'Encontro presencial',
                                'aula_especial' => 'Aula especial',
                            ])
                            ->default('academico')
                            ->required()
                            ->native(false),

                        Forms\Components\ColorPicker::make('cor')
                            ->label('Cor no calendário')
                            ->default('#7c3aed')
                            ->required(),

                        Forms\Components\DateTimePicker::make('data_inicio')
                            ->label('Data de início')
                            ->seconds(false)
                            ->required()
                            ->native(false),

                        Forms\Components\DateTimePicker::make('data_fim')
                            ->label('Data de término')
                            ->seconds(false)
                            ->native(false)
                            ->afterOrEqual('data_inicio'),

                        Forms\Components\Toggle::make('ativo')
                            ->label('Evento ativo')
                            ->default(true)
                            ->inline(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('titulo')
                    ->label('Evento')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->descricao ? \Illuminate\Support\Str::limit($record->descricao, 60) : null),

                Tables\Columns\TextColumn::make('curso.titulo')
                    ->label('Curso')
                    ->badge()
                    ->placeholder('Geral')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('turma.nome')
                    ->label('Turma')
                    ->badge()
                    ->placeholder('Todas')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'academico' => 'primary',
                        'secretaria' => 'info',
                        'prazo' => 'warning',
                        'prova' => 'danger',
                        'encontro' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'academico' => 'Acadêmico',
                        'secretaria' => 'Secretaria',
                        'prazo' => 'Prazo',
                        'prova' => 'Prova',
                        'encontro' => 'Encontro',
                        'aula_especial' => 'Aula especial',
                        default => ucfirst($state),
                    }),

                Tables\Columns\ColorColumn::make('cor')
                    ->label('Cor'),

                Tables\Columns\TextColumn::make('data_inicio')
                    ->label('Início')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('data_fim')
                    ->label('Fim')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Sem fim'),

                Tables\Columns\IconColumn::make('ativo')
                    ->label('Ativo')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('curso_id')
                    ->label('Curso')
                    ->relationship('curso', 'titulo')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('turma_id')
                    ->label('Turma')
                    ->relationship('turma', 'nome')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options([
                        'academico' => 'Acadêmico',
                        'secretaria' => 'Secretaria',
                        'prazo' => 'Prazo',
                        'prova' => 'Prova',
                        'encontro' => 'Encontro',
                        'aula_especial' => 'Aula especial',
                    ]),

                Tables\Filters\TernaryFilter::make('ativo')
                    ->label('Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('data_inicio')
            ->striped();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEventoCalendarioSecretarias::route('/'),
            'create' => Pages\CreateEventoCalendarioSecretaria::route('/create'),
            'edit' => Pages\EditEventoCalendarioSecretaria::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('ativo', true)->count() ?: null;
    }
}
