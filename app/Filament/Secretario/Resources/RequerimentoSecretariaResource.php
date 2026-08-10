<?php

namespace App\Filament\Secretario\Resources;

use App\Filament\Secretario\Resources\RequerimentoSecretariaResource\Pages;
use App\Models\Requerimento;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class RequerimentoSecretariaResource extends Resource
{
    protected static ?string $model = Requerimento::class;
    protected static ?string $modelLabel = 'Requerimento';
    protected static ?string $pluralModelLabel = 'Requerimentos';
    protected static ?string $navigationLabel = 'Requerimentos';
    protected static ?string $navigationIcon = 'heroicon-o-document-plus';
    protected static ?string $navigationGroup = '📋 Secretaria';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados do Requerimento')
                    ->description('Informações da solicitação do aluno')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Select::make('aluno_id')
                            ->label('Aluno')
                            ->relationship('aluno', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn ($record) => $record !== null)
                            ->native(false)
                            ->prefixIcon('heroicon-o-user'),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('tipo')
                                ->label('Tipo de Requerimento')
                                ->options([
                                    'declaracao_matricula' => '📄 Declaração de Matrícula',
                                    'historico_escolar' => '📚 Histórico Escolar',
                                    'revisao_prova' => '📝 Revisão de Prova',
                                    'troca_turma' => '🔄 Troca de Turma',
                                    'outro' => '📋 Outro',
                                ])
                                ->required()
                                ->native(false)
                                ->live()
                                ->prefixIcon('heroicon-o-clipboard-document-list'),

                            Forms\Components\Select::make('status')
                                ->label('Status')
                                ->options([
                                    'pendente' => '⏳ Pendente',
                                    'em_analise' => '🔍 Em Análise',
                                    'resolvido' => '✅ Resolvido',
                                    'negado' => '❌ Negado',
                                ])
                                ->default('pendente')
                                ->required()
                                ->native(false)
                                ->live()
                                ->prefixIcon('heroicon-o-flag'),
                        ]),

                        Forms\Components\TextInput::make('assunto')
                            ->label('Assunto')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ex: Solicitação de declaração de matrícula')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('descricao')
                            ->label('Descrição Detalhada')
                            ->required()
                            ->rows(4)
                            ->placeholder('Descreva detalhadamente o requerimento...')
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('anexos')
                            ->label('Anexos')
                            ->multiple()
                            ->directory('requerimentos_anexos')
                            ->maxSize(5120)
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->helperText('PDF ou imagens, máx. 5MB cada')
                            ->columnSpanFull()
                            ->downloadable()
                            ->openable(),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Resposta da Secretaria')
                    ->description('Análise e resposta ao requerimento')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        Forms\Components\Textarea::make('resposta')
                            ->label('Resposta/Observações')
                            ->rows(4)
                            ->placeholder('Digite aqui a resposta ou observações...')
                            ->visible(fn (Forms\Get $get) => 
                                in_array($get('status'), ['em_analise', 'resolvido', 'negado'])
                            )
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Placeholder::make('respondido_por_info')
                                ->label('Respondido por')
                                ->content(fn ($record) => 
                                    $record?->respondidoPor?->name ?? 'Não respondido ainda'
                                )
                                ->visible(fn ($record) => $record?->respondido_por !== null),

                            Forms\Components\Placeholder::make('respondido_em_info')
                                ->label('Data da Resposta')
                                ->content(fn ($record) => 
                                    $record?->respondido_em?->format('d/m/Y H:i') ?? '-'
                                )
                                ->visible(fn ($record) => $record?->respondido_em !== null),
                        ]),
                    ])
                    ->collapsible()
                    ->collapsed(fn ($record) => $record === null || $record->status === 'pendente'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => $record->created_at->format('H:i')),

                Tables\Columns\TextColumn::make('aluno.name')
                    ->label('Aluno')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->aluno?->email)
                    ->icon('heroicon-m-user'),

                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'declaracao_matricula' => '📄 Declaração',
                        'historico_escolar' => '📚 Histórico',
                        'revisao_prova' => '📝 Revisão',
                        'troca_turma' => '🔄 Troca Turma',
                        'outro' => '📋 Outro',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('assunto')
                    ->label('Assunto')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->assunto),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'pendente' => 'warning',
                        'em_analise' => 'info',
                        'resolvido' => 'success',
                        'negado' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pendente' => '⏳ Pendente',
                        'em_analise' => '🔍 Em Análise',
                        'resolvido' => '✅ Resolvido',
                        'negado' => '❌ Negado',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('respondido_por')
                    ->label('Respondido por')
                    ->formatStateUsing(fn ($record) => 
                        $record->respondidoPor?->name ?? '-'
                    )
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('respondido_em')
                    ->label('Data Resposta')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pendente' => '⏳ Pendente',
                        'em_analise' => '🔍 Em Análise',
                        'resolvido' => '✅ Resolvido',
                        'negado' => '❌ Negado',
                    ])
                    ->multiple()
                    ->native(false),

                Tables\Filters\SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options([
                        'declaracao_matricula' => '📄 Declaração de Matrícula',
                        'historico_escolar' => '📚 Histórico Escolar',
                        'revisao_prova' => '📝 Revisão de Prova',
                        'troca_turma' => '🔄 Troca de Turma',
                        'outro' => '📋 Outro',
                    ])
                    ->multiple()
                    ->native(false),

                Tables\Filters\Filter::make('sem_resposta')
                    ->label('Sem Resposta')
                    ->query(fn (Builder $query) => $query->whereNull('resposta')),

                Tables\Filters\Filter::make('created_at')
                    ->label('Data de Criação')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('De')
                            ->native(false),
                        Forms\Components\DatePicker::make('until')
                            ->label('Até')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),

                    Tables\Actions\EditAction::make()
                        ->color('warning'),

                    Tables\Actions\Action::make('analisar')
                        ->label('Colocar em Análise')
                        ->icon('heroicon-o-magnifying-glass')
                        ->color('info')
                        ->visible(fn ($record) => $record->status === 'pendente')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update([
                                'status' => 'em_analise',
                                'respondido_por' => auth()->id(),
                                'respondido_em' => now(),
                            ]);
                            
                            Notification::make()
                                ->title('Status alterado para Em Análise')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('resolver')
                        ->label('Marcar como Resolvido')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => in_array($record->status, ['pendente', 'em_analise']))
                        ->form([
                            Forms\Components\Textarea::make('resposta')
                                ->label('Resposta Final')
                                ->required()
                                ->rows(4)
                                ->placeholder('Digite a resposta/solução do requerimento...'),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update([
                                'status' => 'resolvido',
                                'resposta' => $data['resposta'],
                                'respondido_por' => auth()->id(),
                                'respondido_em' => now(),
                            ]);
                            
                            Notification::make()
                                ->title('Requerimento resolvido!')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('negar')
                        ->label('Negar Requerimento')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn ($record) => in_array($record->status, ['pendente', 'em_analise']))
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Textarea::make('resposta')
                                ->label('Motivo da Negação')
                                ->required()
                                ->rows(4)
                                ->placeholder('Explique o motivo da negação...'),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update([
                                'status' => 'negado',
                                'resposta' => $data['resposta'],
                                'respondido_por' => auth()->id(),
                                'respondido_em' => now(),
                            ]);
                            
                            Notification::make()
                                ->title('Requerimento negado')
                                ->warning()
                                ->send();
                        }),

                    Tables\Actions\Action::make('whatsapp')
                        ->label('WhatsApp')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('success')
                        ->url(fn ($record) => 
                            "https://api.whatsapp.com/send?phone=55" . 
                            preg_replace('/[^0-9]/', '', $record->aluno->telefone ?? '') .
                            "&text=Olá {$record->aluno->name}, sobre seu requerimento: {$record->assunto}"
                        )
                        ->openUrlInNewTab()
                        ->visible(fn ($record) => filled($record->aluno?->telefone)),

                    Tables\Actions\DeleteAction::make(),
                ])
                ->label('Ações')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('marcar_em_analise')
                        ->label('Marcar como Em Análise')
                        ->icon('heroicon-o-magnifying-glass')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update([
                                'status' => 'em_analise',
                                'respondido_por' => auth()->id(),
                                'respondido_em' => now(),
                            ]);
                            
                            Notification::make()
                                ->title('Requerimentos marcados como Em Análise')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->emptyStateHeading('Nenhum requerimento cadastrado')
            ->emptyStateDescription('Os requerimentos dos alunos aparecerão aqui')
            ->emptyStateIcon('heroicon-o-document-plus');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRequerimentosSecretaria::route('/'),
            'create' => Pages\CreateRequerimentoSecretaria::route('/create'),
            'edit' => Pages\EditRequerimentoSecretaria::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pendente')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $pendentes = static::getModel()::where('status', 'pendente')->count();
        
        if ($pendentes > 10) return 'danger';
        if ($pendentes > 5) return 'warning';
        if ($pendentes > 0) return 'info';
        
        return 'gray';
    }
}