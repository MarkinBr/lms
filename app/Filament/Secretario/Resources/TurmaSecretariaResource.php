<?php

namespace App\Filament\Secretario\Resources;

use App\Filament\Secretario\Resources\TurmaSecretariaResource\Pages;
use App\Models\Turma;
use App\Models\Curso;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;

class TurmaSecretariaResource extends Resource
{
    protected static ?string $model = Turma::class;
    protected static ?string $modelLabel = 'Turma';
    protected static ?string $pluralModelLabel = 'Turmas';
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Acadêmico';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações da Turma')
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        Forms\Components\Select::make('curso_id')
                            ->label('Curso Vinculado')
                            ->options(Curso::where('ativo', true)->pluck('titulo', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false)
                            ->columnSpanFull()
                            ->prefixIcon('heroicon-o-academic-cap'),

                        Forms\Components\TextInput::make('nome')
                            ->label('Nome da Turma')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ex: 2026.1 Semipresencial - Diaconato')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('ano_letivo')
                            ->label('Ano Letivo')
                            ->numeric()
                            ->default(date('Y'))
                            ->required(),

                        Forms\Components\Select::make('turno')
                            ->label('Sábado do Mês')
                            ->options([
                                '1_sabado' => '🗓️ 1º Sábado',
                                '2_sabado' => '🗓️ 2º Sábado',
                                '3_sabado' => '🗓️ 3º Sábado',
                                '4_sabado' => '🗓️ 4º Sábado',
                            ])
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('sala')
                            ->label('Sala / Local')
                            ->placeholder('Ex: Templo Histórico, Sala 102'),
                    ])->columns(2),

                Forms\Components\Section::make('Financeiro e Prazos')
                    ->icon('heroicon-o-banknotes')
                    ->description('Defina o valor total. A mensalidade será calculada automaticamente.')
                    ->schema([

                        Forms\Components\TextInput::make('valor_total_curso')
                            ->label('Valor Total do Curso (R$)')
                            ->numeric()
                            ->prefix('R$')
                            ->step(0.01)
                            ->live(onBlur: true)
                            ->required(),

                        Forms\Components\TextInput::make('qtd_parcelas')
                            ->label('Quantidade de Parcelas')
                            ->numeric()
                            ->default(6)
                            ->minValue(1)
                            ->maxValue(48)
                            ->live(onBlur: true)
                            ->required(),

                        Forms\Components\Placeholder::make('valor_mensal_calculado')
                            ->label('💰 Valor da Mensalidade (Estimado)')
                            ->content(function (Forms\Get $get) {
                                $total = floatval($get('valor_total_curso') ?? 0);
                                $parcelas = intval($get('qtd_parcelas') ?? 1);

                                if (!$total || !$parcelas || $parcelas == 0) {
                                    return 'R$ 0,00';
                                }

                                $mensal = $total / $parcelas;
                                return 'R$ ' . number_format($mensal, 2, ',', '.');
                            })
                            ->columnSpanFull()
                            ->extraAttributes(['class' => 'text-xl font-bold text-primary-600']),

                        Forms\Components\DatePicker::make('data_inicio')
                            ->label('Data Início')
                            ->displayFormat('d/m/Y')
                            ->native(false),

                        Forms\Components\DatePicker::make('data_fim')
                            ->label('Data Fim')
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->afterOrEqual('data_inicio'),

                        Forms\Components\Toggle::make('ativa')
                            ->label('Turma Ativa?')
                            ->default(true)
                            ->inline(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->label('Turma')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('curso.titulo')
                    ->label('Curso')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ano_letivo')
                    ->label('Ano')
                    ->sortable(),

                Tables\Columns\TextColumn::make('turno')
                    ->label('Sábado')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match($state) {
                        '1_sabado' => '1º Sábado',
                        '2_sabado' => '2º Sábado',
                        '3_sabado' => '3º Sábado',
                        '4_sabado' => '4º Sábado',
                        default => $state ?? '—',
                    })
                    ->color(fn (?string $state): string => match($state) {
                        '1_sabado' => 'primary',
                        '2_sabado' => 'success',
                        '3_sabado' => 'warning',
                        '4_sabado' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('matriculas_count')
                    ->counts('matriculas')
                    ->label('Alunos')
                    ->badge()
                    ->color('success')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('valor_total_curso')
                    ->money('BRL')
                    ->label('Valor Total')
                    ->sortable(),

                Tables\Columns\IconColumn::make('ativa')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('curso_id')
                    ->label('Curso')
                    ->relationship('curso', 'titulo')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('turno')
                    ->label('Sábado')
                    ->options([
                        '1_sabado' => '1º Sábado',
                        '2_sabado' => '2º Sábado',
                        '3_sabado' => '3º Sábado',
                        '4_sabado' => '4º Sábado',
                    ]),

                Tables\Filters\TernaryFilter::make('ativa')
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
            ->defaultSort('ano_letivo', 'desc')
            ->striped();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTurmasSecretaria::route('/'),
            'create' => Pages\CreateTurmaSecretaria::route('/create'),
            'edit' => Pages\EditTurmaSecretaria::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('ativa', true)->count() ?: null;
    }
}