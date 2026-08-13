<?php

namespace App\Filament\Secretario\Resources;

use App\Filament\Secretario\Resources\ChamadaFrequenciaResource\Pages;
use App\Models\ChamadaFrequencia;
use App\Models\Disciplina;
use App\Models\Frequencia;
use App\Models\Matricula;
use App\Models\MatriculaTurma;
use App\Models\Turma;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;

class ChamadaFrequenciaResource extends Resource
{
    protected static ?string $model = ChamadaFrequencia::class;

    protected static ?string $modelLabel = 'Chamada de Frequência';

    protected static ?string $pluralModelLabel = 'Chamadas de Frequência';

    protected static ?string $navigationLabel = 'Frequência';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Acadêmico';

    protected static ?int $navigationSort = 5;

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
                Forms\Components\Section::make('Dados da chamada')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->schema([
                        Forms\Components\Select::make('turma_id')
                            ->label('Turma')
                            ->options(
                                Turma::query()
                                    ->where('ativa', true)
                                    ->orderBy('nome')
                                    ->pluck('nome', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('disciplina_id', null)),

                        Forms\Components\Select::make('disciplina_id')
                            ->label('Disciplina')
                            ->options(function (Forms\Get $get) {
                                $turmaId = $get('turma_id');

                                if (! $turmaId) {
                                    return [];
                                }

                                $turma = Turma::query()->find($turmaId);

                                if (! $turma) {
                                    return [];
                                }

                                return Disciplina::query()
                                    ->where('curso_id', $turma->curso_id)
                                    ->where('ativa', true)
                                    ->orderBy('ordem')
                                    ->pluck('nome', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false)
                            ->disabled(fn (Forms\Get $get) => ! $get('turma_id'))
                            ->helperText('Só lista disciplinas do curso da turma selecionada.'),

                        Forms\Components\DatePicker::make('data_aula')
                            ->label('Data da aula')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->default(now()),

                        Forms\Components\TextInput::make('titulo')
                            ->label('Título (opcional)')
                            ->maxLength(255)
                            ->placeholder('Ex: Encontro 3 — Hermenêutica'),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(ChamadaFrequencia::STATUS_LABELS)
                            ->default(ChamadaFrequencia::STATUS_RASCUNHO)
                            ->required()
                            ->native(false)
                            ->helperText('Somente chamadas concluídas contam para o percentual de frequência.'),

                        Forms\Components\Hidden::make('criada_por')
                            ->default(fn () => auth()->id()),

                        Forms\Components\Hidden::make('concluida_por')
                            ->default(fn () => auth()->id())
                            ->dehydrated(fn (Forms\Get $get) => $get('status') === ChamadaFrequencia::STATUS_CONCLUIDA),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Lista de alunos')
                    ->icon('heroicon-o-user-group')
                    ->description('Alunos com matrícula ativa nesta turma (vínculo atual ou legado). Defina a situação de cada um.')
                    ->schema([
                        Forms\Components\Repeater::make('frequencias')
                            ->relationship()
                            ->label('Alunos da turma')
                            ->schema([
                                Forms\Components\Select::make('matricula_id')
                                    ->label('Aluno')
                                    ->options(function (Forms\Get $get, $livewire) {
                                        $turmaId = $get('../../turma_id') ?? $livewire->record?->turma_id;

                                        if (! $turmaId) {
                                            return [];
                                        }

                                        return Matricula::query()
                                            ->where(function ($q) use ($turmaId) {
                                                $q->where(function ($legado) use ($turmaId) {
                                                    $legado->where('turma_id', $turmaId)->ativas();
                                                })->orWhereHas('turmasHistorico', function ($p) use ($turmaId) {
                                                    $p->where('turmas.id', $turmaId)
                                                        ->where('matricula_turma.status', MatriculaTurma::STATUS_ATIVO);
                                                });
                                            })
                                            ->with('user:id,name')
                                            ->get()
                                            ->unique('id')
                                            ->sortBy(fn ($m) => mb_strtolower($m->user->name ?? ''))
                                            ->mapWithKeys(fn ($m) => [$m->id => $m->user->name ?? ('Matrícula #'.$m->id)])
                                            ->all();
                                    })
                                    ->searchable()
                                    ->required()
                                    ->native(false)
                                    ->distinct(),

                                Forms\Components\Select::make('situacao')
                                    ->label('Situação')
                                    ->options(Frequencia::SITUACAO_LABELS)
                                    ->default(Frequencia::SITUACAO_PRESENTE)
                                    ->required()
                                    ->native(false),

                                Forms\Components\Textarea::make('observacao')
                                    ->label('Observação (opcional)')
                                    ->rows(1)
                                    ->maxLength(500),

                                Forms\Components\Hidden::make('registrado_por')
                                    ->default(fn () => auth()->id()),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('Adicionar aluno')
                            ->reorderable(false)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('data_aula')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable()
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('titulo')
                    ->label('Título')
                    ->placeholder('—')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('turma.nome')
                    ->label('Turma')
                    ->badge()
                    ->color('primary')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('disciplina.nome')
                    ->label('Disciplina')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        ChamadaFrequencia::STATUS_CONCLUIDA => 'success',
                        ChamadaFrequencia::STATUS_RASCUNHO => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => ChamadaFrequencia::STATUS_LABELS[$state] ?? '—'),

                Tables\Columns\TextColumn::make('frequencias_count')
                    ->counts('frequencias')
                    ->label('Registros')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('criadaPor.name')
                    ->label('Criada por')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('concluidaPor.name')
                    ->label('Concluída por')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('turma_id')
                    ->label('Turma')
                    ->relationship('turma', 'nome')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('disciplina_id')
                    ->label('Disciplina')
                    ->relationship('disciplina', 'nome')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('status')
                    ->options(ChamadaFrequencia::STATUS_LABELS),
            ])
            ->actions([
                Tables\Actions\Action::make('concluir')
                    ->label('Concluir')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Concluir chamada')
                    ->modalDescription('Chamadas concluídas passam a contar para o percentual de frequência dos alunos.')
                    ->visible(fn (ChamadaFrequencia $record) => $record->status === ChamadaFrequencia::STATUS_RASCUNHO
                        && auth()->user()?->can('update', $record))
                    ->action(function (ChamadaFrequencia $record) {
                        $record->update([
                            'status' => ChamadaFrequencia::STATUS_CONCLUIDA,
                            'concluida_por' => auth()->id(),
                        ]);
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('data_aula', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChamadasFrequencia::route('/'),
            'create' => Pages\CreateChamadaFrequencia::route('/create'),
            'edit' => Pages\EditChamadaFrequencia::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::rascunhos()->count();

        return $count > 0 ? (string) $count : null;
    }
}
