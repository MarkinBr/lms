<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MatriculaResource\Pages;
use App\Models\Curso;
use App\Models\Matricula;
use App\Models\Turma;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MatriculaResource extends Resource
{
    protected static ?string $model = Matricula::class;

    /** Issue #57 — entrada única no menu: central Gestão Acadêmica. */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $modelLabel = 'Matrícula';

    protected static ?string $pluralModelLabel = 'Matrículas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Vínculo acadêmico')
                    ->description('Associe o aluno a um curso e a uma turma compatível.')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Aluno')
                            ->relationship(
                                'user',
                                'name',
                                fn (Builder $query): Builder => $query
                                    ->where('nivel', 'aluno')
                                    ->where('ativo', true)
                                    ->orderBy('name'),
                            )
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('curso_id')
                            ->label('Curso')
                            ->relationship(
                                'curso',
                                'titulo',
                                fn (Builder $query): Builder => $query->orderBy('titulo'),
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('turma_id', null)),

                        Forms\Components\Select::make('turma_id')
                            ->label('Turma')
                            ->options(function (Forms\Get $get): array {
                                $cursoId = (int) $get('curso_id');

                                if ($cursoId < 1) {
                                    return [];
                                }

                                return Turma::query()
                                    ->where('curso_id', $cursoId)
                                    ->orderByDesc('ano_letivo')
                                    ->orderBy('nome')
                                    ->get(['id', 'nome', 'codigo'])
                                    ->mapWithKeys(function (Turma $turma): array {
                                        $sufixo = filled($turma->codigo) ? " · {$turma->codigo}" : '';

                                        return [$turma->id => "{$turma->nome}{$sufixo}"];
                                    })
                                    ->all();
                            })
                            ->searchable()
                            ->required()
                            ->disabled(fn (Forms\Get $get): bool => ! filled($get('curso_id')))
                            ->helperText('A lista mostra somente turmas pertencentes ao curso selecionado.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Dados da matrícula')
                    ->schema([
                        Forms\Components\TextInput::make('numero_matricula')
                            ->label('Número da matrícula')
                            ->default(fn (): string => static::gerarNumeroMatriculaDisponivel())
                            ->required()
                            ->maxLength(80)
                            ->unique(ignoreRecord: true),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(static::statusOptions())
                            ->default(Matricula::STATUS_ATIVA)
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function (?string $state, Forms\Set $set): void {
                                if ($state !== Matricula::STATUS_CONCLUIDA) {
                                    $set('data_conclusao', null);
                                }
                            }),

                        Forms\Components\DatePicker::make('data_matricula')
                            ->label('Data da matrícula')
                            ->default(now())
                            ->required()
                            ->native(false),

                        Forms\Components\DatePicker::make('data_validade')
                            ->label('Validade do vínculo')
                            ->afterOrEqual('data_matricula')
                            ->native(false),

                        Forms\Components\TextInput::make('dia_vencimento')
                            ->label('Dia de vencimento')
                            ->numeric()
                            ->default(10)
                            ->minValue(1)
                            ->maxValue(28)
                            ->required()
                            ->helperText('Use um dia entre 1 e 28 para funcionar em todos os meses.'),

                        Forms\Components\DatePicker::make('data_conclusao')
                            ->label('Data de conclusão')
                            ->visible(fn (Forms\Get $get): bool => $get('status') === Matricula::STATUS_CONCLUIDA)
                            ->required(fn (Forms\Get $get): bool => $get('status') === Matricula::STATUS_CONCLUIDA)
                            ->afterOrEqual('data_matricula')
                            ->native(false),

                        Forms\Components\Textarea::make('observacoes')
                            ->label('Observações internas')
                            ->rows(4)
                            ->maxLength(5000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_matricula')
                    ->label('Nº da matrícula')
                    ->placeholder('Não informado')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Aluno')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('curso.titulo')
                    ->label('Curso')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('turma.nome')
                    ->label('Turma')
                    ->placeholder('Sem turma')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => static::statusLabel($state))
                    ->color(fn (?string $state): string => static::statusColor($state)),

                Tables\Columns\TextColumn::make('data_matricula')
                    ->label('Matrícula em')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('dia_vencimento')
                    ->label('Vencimento')
                    ->formatStateUsing(fn (?int $state): string => $state ? "Dia {$state}" : 'Não informado')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('curso_id')
                    ->label('Curso')
                    ->relationship('curso', 'titulo')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(static::statusOptions())
                    ->query(function (Builder $query, array $data): Builder {
                        $status = static::normalizarStatus($data['value'] ?? null);

                        return match ($status) {
                            Matricula::STATUS_ATIVA => $query->whereRaw("LOWER(COALESCE(status, '')) IN ('ativo','ativa')"),
                            Matricula::STATUS_TRANCADA => $query->whereRaw("LOWER(COALESCE(status, '')) IN ('trancado','trancada')"),
                            Matricula::STATUS_CONCLUIDA => $query->whereRaw("LOWER(COALESCE(status, '')) IN ('concluido','concluida','concluída')"),
                            Matricula::STATUS_CANCELADA => $query->whereRaw("LOWER(COALESCE(status, '')) IN ('cancelado','cancelada')"),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Validação de integridade aplicada também no servidor, após a validação
     * visual do formulário. Impede IDs adulterados e vínculo turma/curso cruzado.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function prepararDadosAcademicos(array $data, ?Matricula $matricula = null): array
    {
        $userId = (int) ($data['user_id'] ?? 0);
        $cursoId = (int) ($data['curso_id'] ?? 0);
        $turmaId = (int) ($data['turma_id'] ?? 0);
        $numero = trim((string) ($data['numero_matricula'] ?? ''));
        $status = static::normalizarStatus($data['status'] ?? null);
        $diaVencimento = (int) ($data['dia_vencimento'] ?? 0);
        $erros = [];

        if (! User::query()->whereKey($userId)->where('nivel', 'aluno')->where('ativo', true)->exists()) {
            $erros['user_id'] = 'Selecione um aluno ativo e válido.';
        }

        if (! Curso::query()->whereKey($cursoId)->exists()) {
            $erros['curso_id'] = 'Selecione um curso válido.';
        }

        if (! Turma::query()->whereKey($turmaId)->where('curso_id', $cursoId)->exists()) {
            $erros['turma_id'] = 'A turma selecionada não pertence ao curso informado.';
        }

        if (! in_array($status, array_keys(static::statusOptions()), true)) {
            $erros['status'] = 'Selecione um status de matrícula válido.';
        }

        if ($numero === '') {
            $erros['numero_matricula'] = 'Informe o número da matrícula.';
        } else {
            $numeroEmUso = Matricula::query()
                ->where('numero_matricula', $numero)
                ->when($matricula, fn (Builder $query): Builder => $query->whereKeyNot($matricula->getKey()))
                ->exists();

            if ($numeroEmUso) {
                $erros['numero_matricula'] = 'Este número de matrícula já está em uso.';
            }
        }

        if ($diaVencimento < 1 || $diaVencimento > 28) {
            $erros['dia_vencimento'] = 'O dia de vencimento deve estar entre 1 e 28.';
        }

        if ($status === Matricula::STATUS_CONCLUIDA && blank($data['data_conclusao'] ?? null)) {
            $erros['data_conclusao'] = 'Informe a data de conclusão.';
        }

        if ($erros !== []) {
            throw ValidationException::withMessages($erros);
        }

        $data['user_id'] = $userId;
        $data['curso_id'] = $cursoId;
        $data['turma_id'] = $turmaId;
        $data['numero_matricula'] = $numero;
        $data['status'] = $status;
        $data['dia_vencimento'] = $diaVencimento;

        if ($status !== Matricula::STATUS_CONCLUIDA) {
            $data['data_conclusao'] = null;
        }

        return $data;
    }

    /** @return array<string, string> */
    public static function statusOptions(): array
    {
        return [
            Matricula::STATUS_ATIVA => 'Ativa',
            Matricula::STATUS_TRANCADA => 'Trancada',
            Matricula::STATUS_CONCLUIDA => 'Concluída',
            Matricula::STATUS_CANCELADA => 'Cancelada',
        ];
    }

    public static function normalizarStatus(mixed $status): ?string
    {
        $normalizado = Str::of((string) $status)
            ->trim()
            ->lower()
            ->ascii()
            ->toString();

        return match ($normalizado) {
            'ativo', 'ativa' => Matricula::STATUS_ATIVA,
            'trancado', 'trancada' => Matricula::STATUS_TRANCADA,
            'concluido', 'concluida' => Matricula::STATUS_CONCLUIDA,
            'cancelado', 'cancelada' => Matricula::STATUS_CANCELADA,
            default => $normalizado !== '' ? $normalizado : null,
        };
    }

    public static function gerarNumeroMatriculaDisponivel(): string
    {
        do {
            $numero = now()->format('YmdHis').'-'.Str::upper(Str::random(4));
        } while (Matricula::query()->where('numero_matricula', $numero)->exists());

        return $numero;
    }

    private static function statusLabel(?string $status): string
    {
        $normalizado = static::normalizarStatus($status);

        return static::statusOptions()[$normalizado] ?? ($status ?: 'Não informado');
    }

    private static function statusColor(?string $status): string
    {
        return match (static::normalizarStatus($status)) {
            Matricula::STATUS_ATIVA => 'success',
            Matricula::STATUS_TRANCADA => 'warning',
            Matricula::STATUS_CONCLUIDA => 'info',
            Matricula::STATUS_CANCELADA => 'danger',
            default => 'gray',
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMatriculas::route('/'),
            'create' => Pages\CreateMatricula::route('/create'),
            'edit' => Pages\EditMatricula::route('/{record}/edit'),
        ];
    }
}
