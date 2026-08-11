<?php

namespace App\Filament\Secretario\Resources;

use App\Filament\Secretario\Resources\TurmaSecretariaResource\Pages;
use App\Filament\Secretario\Resources\TurmaSecretariaResource\RelationManagers\MatriculasTurmaRelationManager;
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

class TurmaSecretariaResource extends Resource
{
    protected static ?string $model = Turma::class;

    protected static ?string $modelLabel = 'Turma';

    protected static ?string $pluralModelLabel = 'Turmas';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Acadêmico';

    protected static ?int $navigationSort = 2;

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
                            ->prefixIcon('heroicon-o-academic-cap')
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('disciplina_id', null)),

                        Forms\Components\Select::make('periodo_letivo_id')
                            ->label('Período Letivo')
                            ->options(PeriodoLetivo::orderByDesc('data_inicio')->pluck('nome', 'id'))
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Deixe vazio apenas para turmas legadas sem período migrado.'),

                        Forms\Components\Select::make('disciplina_id')
                            ->label('Disciplina')
                            ->options(function (Forms\Get $get) {
                                if (! $get('curso_id')) {
                                    return [];
                                }

                                return Disciplina::query()
                                    ->where('curso_id', $get('curso_id'))
                                    ->where('ativa', true)
                                    ->orderBy('ordem')
                                    ->pluck('nome', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->disabled(fn (Forms\Get $get) => ! $get('curso_id'))
                            ->nullable(),

                        Forms\Components\Select::make('professor_id')
                            ->label('Professor')
                            ->options(User::where('nivel', 'professor')->where('ativo', true)->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\TextInput::make('nome')
                            ->label('Nome da Turma')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ex: 2026.1 Semipresencial - Diaconato')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('codigo')
                            ->label('Código')
                            ->nullable()
                            ->helperText('Código único por período letivo quando período e código forem informados.'),

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

                        Forms\Components\TextInput::make('capacidade')
                            ->numeric()
                            ->minValue(1)
                            ->nullable(),

                        Forms\Components\Select::make('modalidade')
                            ->options(Turma::MODALIDADE_LABELS)
                            ->nullable()
                            ->native(false),

                        Forms\Components\Select::make('status')
                            ->options(Turma::STATUS_LABELS)
                            ->default(Turma::STATUS_PLANEJADA)
                            ->nullable()
                            ->native(false),
                    ])
                    ->columns(2),

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

                                if (! $total || ! $parcelas || $parcelas == 0) {
                                    return 'R$ 0,00';
                                }

                                $mensal = $total / $parcelas;

                                return 'R$ '.number_format($mensal, 2, ',', '.');
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
                            ->inline(false)
                            ->helperText('Campo legado. O status também controla a situação da turma.'),
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
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('codigo')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('curso.titulo')
                    ->label('Curso')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                Tables\Columns\TextColumn::make('periodoLetivo.nome')
                    ->label('Período')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('disciplina.nome')
                    ->label('Disciplina')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('professor.name')
                    ->label('Professor')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('ano_letivo')
                    ->label('Ano')
                    ->sortable(),

                Tables\Columns\TextColumn::make('turno')
                    ->label('Sábado')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        '1_sabado' => '1º Sábado',
                        '2_sabado' => '2º Sábado',
                        '3_sabado' => '3º Sábado',
                        '4_sabado' => '4º Sábado',
                        default => $state ?? '—',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        '1_sabado' => 'primary',
                        '2_sabado' => 'success',
                        '3_sabado' => 'warning',
                        '4_sabado' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('modalidade')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Turma::MODALIDADE_LABELS[$state] ?? $state ?? '—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('capacidade')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'gray' => Turma::STATUS_PLANEJADA,
                        'success' => Turma::STATUS_ABERTA,
                        'info' => Turma::STATUS_ENCERRADA,
                        'danger' => Turma::STATUS_CANCELADA,
                    ])
                    ->formatStateUsing(fn (?string $state): string => Turma::STATUS_LABELS[$state] ?? $state ?? '—'),

                Tables\Columns\TextColumn::make('matriculas_count')
                    ->counts('matriculas')
                    ->label('Alunos (legado)')
                    ->badge()
                    ->color('success')
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('matriculasTurmas_count')
                    ->counts('matriculasTurmas')
                    ->label('Matrículas N:N')
                    ->badge()
                    ->color('info')
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('valor_total_curso')
                    ->money('BRL')
                    ->label('Valor Total')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('ativa')
                    ->label('Ativa (legado)')
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

                Tables\Filters\SelectFilter::make('periodo_letivo_id')
                    ->label('Período')
                    ->relationship('periodoLetivo', 'nome')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('disciplina_id')
                    ->label('Disciplina')
                    ->relationship('disciplina', 'nome')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('professor_id')
                    ->label('Professor')
                    ->relationship('professor', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('status')
                    ->options(Turma::STATUS_LABELS),

                Tables\Filters\SelectFilter::make('modalidade')
                    ->options(Turma::MODALIDADE_LABELS),

                Tables\Filters\SelectFilter::make('turno')
                    ->label('Sábado')
                    ->options([
                        '1_sabado' => '1º Sábado',
                        '2_sabado' => '2º Sábado',
                        '3_sabado' => '3º Sábado',
                        '4_sabado' => '4º Sábado',
                    ]),

                Tables\Filters\TernaryFilter::make('ativa')
                    ->label('Status legado'),
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
        return [
            MatriculasTurmaRelationManager::class,
        ];
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
