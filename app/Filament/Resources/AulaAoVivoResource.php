<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AulaAoVivoResource\Pages;
use App\Models\AulaAoVivo;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class AulaAoVivoResource extends Resource
{
    protected static ?string $model = AulaAoVivo::class;

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';

    protected static ?string $navigationLabel = 'Aulas ao Vivo';

    protected static ?string $modelLabel = 'Aula ao Vivo';

    protected static ?string $pluralModelLabel = 'Aulas ao Vivo';

    protected static ?string $navigationGroup = 'Acadêmico';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informações da Transmissão')
                    ->schema([
                        Forms\Components\Select::make('curso_id')
                            ->label('Curso')
                            ->relationship('curso', 'titulo')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->columnSpan(1),

                        Forms\Components\Select::make('turma_id')
                            ->label('Turma (Opcional)')
                            ->relationship('turma', 'nome', fn ($query, $get) => $query->when($get('curso_id'), fn ($q, $cursoId) => $q->where('curso_id', $cursoId)
                            )
                            )
                            ->searchable()
                            ->preload()
                            ->helperText('Sem turma vinculada, o encontro não aparece no calendário individual dos alunos.')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('titulo')
                            ->label('Título da Aula')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->placeholder('Ex: Introdução ao Antigo Testamento'),

                        Forms\Components\Textarea::make('descricao')
                            ->label('Descrição')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Modalidade e Agendamento')
                    ->schema([
                        Forms\Components\Select::make('modalidade')
                            ->label('Modalidade')
                            ->options(AulaAoVivo::MODALIDADE_LABELS)
                            ->required()
                            ->native(false)
                            ->reactive()
                            ->columnSpan(1),

                        Forms\Components\DateTimePicker::make('data_hora')
                            ->label('Data e Hora')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d/m/Y H:i')
                            ->seconds(false)
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('local')
                            ->label('Local')
                            ->placeholder('Ex: Templo Histórico, Sala 3')
                            ->maxLength(255)
                            ->visible(fn (Forms\Get $get) => $get('modalidade') === AulaAoVivo::MODALIDADE_PRESENCIAL)
                            ->required(fn (Forms\Get $get) => $get('modalidade') === AulaAoVivo::MODALIDADE_PRESENCIAL)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('link_transmissao')
                            ->label('Link da Transmissão')
                            ->url()
                            ->placeholder('https://youtube.com/live/...')
                            ->helperText('YouTube Live, Google Meet, Zoom, etc.')
                            ->visible(fn (Forms\Get $get) => $get('modalidade') !== AulaAoVivo::MODALIDADE_PRESENCIAL)
                            ->required(fn (Forms\Get $get) => $get('modalidade') === AulaAoVivo::MODALIDADE_ONLINE)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('duracao')
                            ->label('Duração (minutos)')
                            ->numeric()
                            ->default(60)
                            ->required()
                            ->suffix('min')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Situação do Encontro')
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

                Section::make('Gravação')
                    ->schema([
                        Forms\Components\Toggle::make('gravacao_disponivel')
                            ->label('Gravação Disponível')
                            ->reactive()
                            ->helperText('Marque quando a gravação estiver pronta'),

                        Forms\Components\TextInput::make('gravacao_url')
                            ->label('URL da Gravação')
                            ->url()
                            ->placeholder('https://youtube.com/watch?v=...')
                            ->visible(fn ($get) => $get('gravacao_disponivel'))
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
                    ->label('Título')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->description(fn ($record) => $record->descricao ? Str::limit($record->descricao, 50) : null),

                Tables\Columns\TextColumn::make('curso.titulo')
                    ->label('Curso')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('turma.nome')
                    ->label('Turma')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Todas'),

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
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => $record->data_hora->isPast() ? 'gray' : 'success'),

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

                Tables\Columns\TextColumn::make('duracao')
                    ->label('Duração')
                    ->suffix(' min')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('link_transmissao')
                    ->label('Link')
                    ->boolean()
                    ->trueIcon('heroicon-o-link')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('gravacao_disponivel')
                    ->label('Gravação')
                    ->boolean()
                    ->trueIcon('heroicon-o-video-camera')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
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

                Tables\Filters\TernaryFilter::make('gravacao_disponivel')
                    ->label('Gravação')
                    ->placeholder('Todas')
                    ->trueLabel('Com gravação')
                    ->falseLabel('Sem gravação'),

                Tables\Filters\Filter::make('proximas')
                    ->label('Próximas Aulas')
                    ->query(fn ($query) => $query->where('data_hora', '>=', now())),

                Tables\Filters\Filter::make('passadas')
                    ->label('Aulas Passadas')
                    ->query(fn ($query) => $query->where('data_hora', '<', now())),
            ])
            ->actions([
                Tables\Actions\Action::make('copiar_link')
                    ->label('Copiar Link')
                    ->icon('heroicon-o-clipboard')
                    ->action(fn ($record) => $record->link_transmissao)
                    ->visible(fn ($record) => ! empty($record->link_transmissao))
                    ->requiresConfirmation(false)
                    ->modalHeading('Link da Transmissão')
                    ->modalContent(fn ($record) => view('filament.actions.copiar-link', ['link' => $record->link_transmissao]))
                    ->modalSubmitActionLabel('Fechar'),

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
            ->defaultSort('data_hora', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAulasAoVivo::route('/'),
            'create' => Pages\CreateAulaAoVivo::route('/create'),
            'edit' => Pages\EditAulaAoVivo::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('data_hora', '>=', now())->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }
}
