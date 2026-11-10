<?php

namespace App\Filament\Secretario\Resources;

use App\Filament\Secretario\Resources\AulaAoVivoSecretariaResource\Pages;
use App\Models\AulaAoVivo;
use App\Models\Curso;
use App\Models\Turma;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class AulaAoVivoSecretariaResource extends Resource
{
    protected static ?string $model = AulaAoVivo::class;

    protected static ?string $modelLabel = 'Encontro (Aula ao Vivo)';

    protected static ?string $pluralModelLabel = 'Encontros (Aulas ao Vivo)';

    protected static ?string $navigationLabel = 'Aulas ao Vivo';

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';

    protected static ?string $navigationGroup = 'Acadêmico';

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
                Forms\Components\Section::make('Curso, turma e encontro')
                    ->icon('heroicon-o-video-camera')
                    ->schema([
                        Forms\Components\Select::make('curso_id')
                            ->label('Curso')
                            ->options(Curso::query()->where('ativo', true)->orderBy('titulo')->pluck('titulo', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('turma_id', null)),

                        Forms\Components\Select::make('turma_id')
                            ->label('Turma (Opcional)')
                            ->options(function (Forms\Get $get) {
                                if (! $get('curso_id')) {
                                    return [];
                                }

                                return Turma::query()
                                    ->where('curso_id', $get('curso_id'))
                                    ->orderBy('nome')
                                    ->pluck('nome', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->disabled(fn (Forms\Get $get) => ! $get('curso_id'))
                            ->native(false)
                            ->helperText('Sem turma vinculada, o encontro não aparece no calendário individual dos alunos.'),

                        Forms\Components\TextInput::make('titulo')
                            ->label('Título do encontro')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->placeholder('Ex: Encontro presencial — Módulo 3'),

                        Forms\Components\Textarea::make('descricao')
                            ->label('Descrição')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Modalidade e agendamento')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Forms\Components\Select::make('modalidade')
                            ->label('Modalidade')
                            ->options(AulaAoVivo::MODALIDADE_LABELS)
                            ->required()
                            ->native(false)
                            ->live(),

                        Forms\Components\DateTimePicker::make('data_hora')
                            ->label('Data e hora')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y H:i')
                            ->seconds(false),

                        Forms\Components\TextInput::make('local')
                            ->label('Local')
                            ->placeholder('Ex: Templo Histórico, Sala 3')
                            ->maxLength(255)
                            ->visible(fn (Forms\Get $get) => $get('modalidade') === AulaAoVivo::MODALIDADE_PRESENCIAL)
                            ->required(fn (Forms\Get $get) => $get('modalidade') === AulaAoVivo::MODALIDADE_PRESENCIAL)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('link_transmissao')
                            ->label('Link da transmissão')
                            ->url()
                            ->placeholder('https://youtube.com/live/...')
                            ->visible(fn (Forms\Get $get) => $get('modalidade') !== AulaAoVivo::MODALIDADE_PRESENCIAL)
                            ->required(fn (Forms\Get $get) => $get('modalidade') === AulaAoVivo::MODALIDADE_ONLINE)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('duracao')
                            ->label('Duração (minutos)')
                            ->numeric()
                            ->default(60)
                            ->required()
                            ->suffix('min'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Situação')
                    ->icon('heroicon-o-flag')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(AulaAoVivo::STATUS_LABELS)
                            ->default(AulaAoVivo::STATUS_AGENDADA)
                            ->required()
                            ->native(false),

                        Forms\Components\Textarea::make('observacoes')
                            ->label('Observações para os alunos')
                            ->rows(2)
                            ->helperText('Fica visível para os alunos da turma no calendário.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('titulo')
                    ->label('Encontro')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->description(fn ($record) => $record->descricao ? Str::limit($record->descricao, 50) : null),

                Tables\Columns\TextColumn::make('curso.titulo')
                    ->label('Curso')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                Tables\Columns\TextColumn::make('turma.nome')
                    ->label('Turma')
                    ->badge()
                    ->placeholder('Todas')
                    ->sortable(),

                Tables\Columns\TextColumn::make('modalidade')
                    ->label('Modalidade')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        AulaAoVivo::MODALIDADE_ONLINE => 'info',
                        AulaAoVivo::MODALIDADE_PRESENCIAL => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => AulaAoVivo::MODALIDADE_LABELS[$state] ?? '—'),

                Tables\Columns\TextColumn::make('data_hora')
                    ->label('Data/Hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        AulaAoVivo::STATUS_AGENDADA => 'success',
                        AulaAoVivo::STATUS_CANCELADA => 'danger',
                        AulaAoVivo::STATUS_REALIZADA => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => AulaAoVivo::STATUS_LABELS[$state] ?? AulaAoVivo::STATUS_LABELS[AulaAoVivo::STATUS_AGENDADA]),
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

                Tables\Filters\SelectFilter::make('modalidade')
                    ->label('Modalidade')
                    ->options(AulaAoVivo::MODALIDADE_LABELS),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(AulaAoVivo::STATUS_LABELS),

                Tables\Filters\Filter::make('proximas')
                    ->label('Próximos encontros')
                    ->query(fn ($query) => $query->where('data_hora', '>=', now())),

                Tables\Filters\Filter::make('passadas')
                    ->label('Encontros passados')
                    ->query(fn ($query) => $query->where('data_hora', '<', now())),
            ])
            ->actions([
                Tables\Actions\Action::make('cancelar')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cancelar encontro')
                    ->modalDescription('O encontro continua no histórico, apenas marcado como cancelado. Nenhuma matrícula, mensalidade, presença ou nota é alterada.')
                    ->modalSubmitActionLabel('Cancelar encontro')
                    ->visible(fn ($record) => $record->status !== AulaAoVivo::STATUS_CANCELADA && auth()->user()?->can('update', $record))
                    ->action(fn (AulaAoVivo $record) => $record->cancelar()),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('data_hora', 'desc')
            ->striped();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAulasAoVivoSecretaria::route('/'),
            'create' => Pages\CreateAulaAoVivoSecretaria::route('/create'),
            'edit' => Pages\EditAulaAoVivoSecretaria::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('data_hora', '>=', now())->count();

        return $count > 0 ? (string) $count : null;
    }
}
