<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TurmaResource\Pages;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\PeriodoLetivo;
use App\Models\Turma;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;

class TurmaResource extends Resource
{
    protected static ?string $model = Turma::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Turma';

    protected static ?string $pluralModelLabel = 'Turmas';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Oferta acadêmica')
                ->description('Uma turma representa a oferta de uma disciplina em um período letivo.')
                ->schema([
                    Forms\Components\Select::make('curso_id')
                        ->label('Curso')
                        ->options(Curso::query()->where('ativo', true)->orderBy('titulo')->pluck('titulo', 'id'))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (callable $set) => $set('disciplina_id', null)),
                    Forms\Components\Select::make('periodo_letivo_id')
                        ->label('Período letivo')
                        ->options(PeriodoLetivo::query()->orderByDesc('data_inicio')->pluck('nome', 'id'))
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('disciplina_id')
                        ->label('Disciplina')
                        ->options(function (Forms\Get $get): array {
                            if (! $get('curso_id')) {
                                return [];
                            }

                            return Disciplina::query()
                                ->where('curso_id', $get('curso_id'))
                                ->where('ativa', true)
                                ->whereNotNull('modulo_id')
                                ->orderBy('ordem')
                                ->pluck('nome', 'id')
                                ->all();
                        })
                        ->searchable()
                        ->required()
                        ->disabled(fn (Forms\Get $get): bool => ! $get('curso_id')),
                    Forms\Components\Select::make('professor_id')
                        ->label('Professor')
                        ->options(User::query()->where('nivel', 'professor')->where('ativo', true)->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->preload(),
                    Forms\Components\TextInput::make('nome')
                        ->label('Nome da turma')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('codigo')
                        ->label('Código')
                        ->required()
                        ->maxLength(80)
                        ->helperText('O código deve ser único dentro do período letivo.'),
                    Forms\Components\TextInput::make('ano_letivo')
                        ->label('Ano letivo')
                        ->numeric()
                        ->default((int) date('Y'))
                        ->required(),
                    Forms\Components\Select::make('turno')
                        ->label('Encontro mensal')
                        ->options([
                            '1_sabado' => '1º sábado',
                            '2_sabado' => '2º sábado',
                            '3_sabado' => '3º sábado',
                            '4_sabado' => '4º sábado',
                        ])
                        ->native(false)
                        ->required(),
                    Forms\Components\TextInput::make('sala')
                        ->label('Sala / local')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('capacidade')
                        ->numeric()
                        ->minValue(1),
                    Forms\Components\Select::make('modalidade')
                        ->options([Turma::MODALIDADE_HIBRIDA => 'Híbrido (on-line + presencial)'])
                        ->default(Turma::MODALIDADE_HIBRIDA)
                        ->required()
                        ->disabled()
                        ->dehydrated(),
                    Forms\Components\Select::make('status')
                        ->options(Turma::STATUS_LABELS)
                        ->default(Turma::STATUS_PLANEJADA)
                        ->required()
                        ->native(false),
                ])
                ->columns(2),
            Forms\Components\Section::make('Financeiro e calendário')
                ->schema([
                    Forms\Components\TextInput::make('valor_total_curso')
                        ->label('Valor total (R$)')
                        ->numeric()
                        ->prefix('R$')
                        ->step(0.01)
                        ->required(),
                    Forms\Components\TextInput::make('qtd_parcelas')
                        ->label('Parcelas')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(48)
                        ->default(6)
                        ->required(),
                    Forms\Components\DatePicker::make('data_inicio')
                        ->label('Data de início')
                        ->native(false),
                    Forms\Components\DatePicker::make('data_fim')
                        ->label('Data de término')
                        ->native(false)
                        ->afterOrEqual('data_inicio'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->label('Turma')
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('codigo')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('curso.titulo')->label('Curso')->sortable(),
                Tables\Columns\TextColumn::make('periodoLetivo.nome')->label('Período')->sortable(),
                Tables\Columns\TextColumn::make('disciplina.nome')->label('Disciplina'),
                Tables\Columns\TextColumn::make('professor.name')->label('Professor')->toggleable(),
                Tables\Columns\TextColumn::make('modalidade')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Turma::MODALIDADE_LABELS[$state] ?? 'Não definida'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Turma::STATUS_LABELS[$state] ?? 'Não definido'),
                Tables\Columns\TextColumn::make('capacidade')->label('Vagas')->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('curso_id')->relationship('curso', 'titulo')->searchable()->preload(),
                Tables\Filters\SelectFilter::make('periodo_letivo_id')->label('Período')->relationship('periodoLetivo', 'nome')->searchable()->preload(),
                Tables\Filters\SelectFilter::make('disciplina_id')->label('Disciplina')->relationship('disciplina', 'nome')->searchable()->preload(),
                Tables\Filters\SelectFilter::make('status')->options(Turma::STATUS_LABELS),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('ano_letivo', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTurmas::route('/'),
            'create' => Pages\CreateTurma::route('/create'),
            'edit' => Pages\EditTurma::route('/{record}/edit'),
        ];
    }
}
