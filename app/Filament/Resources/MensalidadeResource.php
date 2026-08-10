<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MensalidadeResource\Pages;
use App\Filament\Resources\MensalidadeResource\Widgets\MensalidadeStats;
use App\Models\Mensalidade;
use App\Services\MercadoPagoService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class MensalidadeResource extends Resource
{
    protected static ?string $model = Mensalidade::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Mensalidades';
    protected static ?string $modelLabel = 'Mensalidade';
    protected static ?string $pluralModelLabel = 'Mensalidades';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados da Mensalidade')
                    ->schema([
                        Forms\Components\Select::make('matricula_id')
                            ->relationship('matricula', 'id')
                            ->label('Matrícula')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->getOptionLabelFromRecordUsing(fn ($record) => 
                                $record->aluno->nome . ' - ' . $record->turma->nome
                            ),

                        Forms\Components\TextInput::make('valor')
                            ->label('Valor')
                            ->prefix('R$')
                            ->numeric()
                            ->required()
                            ->default(0.00)
                            ->minValue(0.01)
                            ->step(0.01),

                        Forms\Components\DatePicker::make('data_vencimento')
                            ->label('Data de Vencimento')
                            ->required()
                            ->default(now()->addMonth())
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'Pendente' => 'Pendente',
                                'Pago' => 'Pago',
                                'Atrasado' => 'Atrasado',
                                'Cancelado' => 'Cancelado',
                            ])
                            ->default('Pendente')
                            ->required(),

                        Forms\Components\DatePicker::make('data_pagamento')
                            ->label('Data do Pagamento')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->visible(fn ($get) => $get('status') === 'Pago'),

                        Forms\Components\Textarea::make('observacao')
                            ->label('Observação')
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])->columns(3),

                Forms\Components\Section::make('Informações de Pagamento')
                    ->schema([
                        Forms\Components\TextInput::make('transacao_id')
                            ->label('ID Transação MP')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('metodo_pagamento')
                            ->label('Método')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('linha_digitavel')
                            ->label('Linha Digitável')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('pix_copia_cola')
                            ->label('PIX Copia e Cola')
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record && $record->transacao_id)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('matricula.aluno.nome')
                    ->label('Aluno')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('matricula.turma.nome')
                    ->label('Turma')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('valor')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('data_vencimento')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => $record->data_vencimento->isPast() && $record->status !== 'Pago' ? 'danger' : null),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'Pago',
                        'warning' => 'Pendente',
                        'danger' => 'Atrasado',
                        'gray' => 'Cancelado',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'Pago',
                        'heroicon-o-clock' => 'Pendente',
                        'heroicon-o-exclamation-circle' => 'Atrasado',
                        'heroicon-o-x-circle' => 'Cancelado',
                    ]),

                Tables\Columns\TextColumn::make('metodo_pagamento')
                    ->label('Método')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pix' => 'PIX',
                        'bolbradesco' => 'Boleto',
                        default => $state ? strtoupper($state) : '-'
                    }),

                Tables\Columns\TextColumn::make('data_pagamento')
                    ->label('Data Pagto')
                    ->date('d/m/Y')
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('data_vencimento', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'Pendente' => 'Pendente',
                        'Pago' => 'Pago',
                        'Atrasado' => 'Atrasado',
                        'Cancelado' => 'Cancelado',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('metodo_pagamento')
                    ->label('Método')
                    ->options([
                        'pix' => 'PIX',
                        'bolbradesco' => 'Boleto',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('vencimento')
                    ->form([
                        Forms\Components\DatePicker::make('vencimento_de')
                            ->label('Vencimento de'),
                        Forms\Components\DatePicker::make('vencimento_ate')
                            ->label('Até'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['vencimento_de'],
                                fn (Builder $query, $date): Builder => $query->whereDate('data_vencimento', '>=', $date),
                            )
                            ->when(
                                $data['vencimento_ate'],
                                fn (Builder $query, $date): Builder => $query->whereDate('data_vencimento', '<=', $date),
                            );
                    }),

                Tables\Filters\Filter::make('vencidas')
                    ->label('Apenas Vencidas')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('data_vencimento', '<', now())
                        ->where('status', '!=', 'Pago')
                    ),
            ])
            ->actions([
                // Ação: Gerar PIX
                Tables\Actions\Action::make('gerarPix')
                    ->label('Gerar PIX')
                    ->icon('heroicon-o-qr-code')
                    ->color('success')
                    ->visible(fn ($record) => !$record->transacao_id && $record->status !== 'Pago')
                    ->requiresConfirmation()
                    ->modalHeading('Gerar Pagamento PIX')
                    ->modalDescription('Deseja gerar um código PIX para esta mensalidade?')
                    ->action(function (Mensalidade $record) {
                        try {
                            $mpService = new MercadoPagoService();
                            $resultado = $mpService->gerarPix($record);

                            Notification::make()
                                ->title('PIX gerado com sucesso!')
                                ->success()
                                ->body('O código PIX foi gerado. Clique em "Ver PIX" para visualizar.')
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Erro ao gerar PIX')
                                ->danger()
                                ->body($e->getMessage())
                                ->send();

                            Log::error('Erro ao gerar PIX', [
                                'mensalidade_id' => $record->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }),

                // Ação: Gerar Boleto
                Tables\Actions\Action::make('gerarBoleto')
                    ->label('Gerar Boleto')
                    ->icon('heroicon-o-document-text')
                    ->color('warning')
                    ->visible(fn ($record) => !$record->transacao_id && $record->status !== 'Pago')
                    ->requiresConfirmation()
                    ->modalHeading('Gerar Boleto Bancário')
                    ->modalDescription('Deseja gerar um boleto para esta mensalidade?')
                    ->action(function (Mensalidade $record) {
                        try {
                            $mpService = new MercadoPagoService();
                            $resultado = $mpService->gerarBoleto($record);

                            Notification::make()
                                ->title('Boleto gerado com sucesso!')
                                ->success()
                                ->body('O boleto foi gerado. Clique em "Ver Boleto" para visualizar.')
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Erro ao gerar boleto')
                                ->danger()
                                ->body($e->getMessage())
                                ->send();

                            Log::error('Erro ao gerar Boleto', [
                                'mensalidade_id' => $record->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }),

                // Ação: Ver PIX
                Tables\Actions\Action::make('verPix')
                    ->label('Ver PIX')
                    ->icon('heroicon-o-qr-code')
                    ->color('info')
                    ->visible(fn ($record) => $record->metodo_pagamento === 'pix' && $record->pix_copia_cola)
                    ->modalHeading('Informações PIX')
                    ->modalContent(fn ($record) => view('filament.modals.pix-info', [
                        'mensalidade' => $record
                    ]))
                    ->modalSubmitActionLabel('Fechar')
                    ->modalCancelAction(false),

                // Ação: Ver Boleto
                Tables\Actions\Action::make('verBoleto')
                    ->label('Ver Boleto')
                    ->icon('heroicon-o-document-text')
                    ->color('warning')
                    ->visible(fn ($record) => $record->metodo_pagamento === 'bolbradesco' && $record->link_boleto)
                    ->url(fn ($record) => $record->link_boleto)
                    ->openUrlInNewTab(),

                // Ação: Confirmar Pagamento
                Tables\Actions\Action::make('confirmarPagamento')
                    ->label('Confirmar Pagamento')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status !== 'Pago')
                    ->requiresConfirmation()
                    ->modalHeading('Confirmar Pagamento')
                    ->modalDescription('Tem certeza que deseja marcar esta mensalidade como paga?')
                    ->form([
                        Forms\Components\DatePicker::make('data_pagamento')
                            ->label('Data do Pagamento')
                            ->default(now())
                            ->required()
                            ->native(false),
                    ])
                    ->action(function (Mensalidade $record, array $data) {
                        $record->update([
                            'status' => 'Pago',
                            'data_pagamento' => $data['data_pagamento'],
                        ]);

                        Notification::make()
                            ->title('Pagamento confirmado!')
                            ->success()
                            ->send();
                    }),

                // Ação: Cancelar Pagamento
                Tables\Actions\Action::make('cancelarPagamento')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->transacao_id && $record->status !== 'Pago' && $record->status !== 'Cancelado')
                    ->requiresConfirmation()
                    ->modalHeading('Cancelar Pagamento')
                    ->modalDescription('Deseja cancelar este pagamento no Mercado Pago?')
                    ->action(function (Mensalidade $record) {
                        try {
                            $mpService = new MercadoPagoService();
                            $mpService->cancelarPagamento($record);

                            Notification::make()
                                ->title('Pagamento cancelado!')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Erro ao cancelar')
                                ->danger()
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('marcarComoPago')
                        ->label('Marcar como Pago')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update([
                                'status' => 'Pago',
                                'data_pagamento' => now(),
                            ]);

                            Notification::make()
                                ->title('Mensalidades atualizadas!')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('Nenhuma mensalidade encontrada')
            ->emptyStateDescription('Comece criando uma nova mensalidade.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMensalidades::route('/'),
            'create' => Pages\CreateMensalidade::route('/create'),
            'edit' => Pages\EditMensalidade::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            MensalidadeStats::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'Atrasado')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}