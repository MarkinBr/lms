<?php

namespace App\Filament\Secretario\Resources;

use App\Filament\Secretario\Resources\MensalidadeSecretariaResource\Pages;
use App\Models\Mensalidade;
use App\Models\Matricula;
use App\Services\MercadoPagoService;
use App\Services\WhatsAppService;
use App\Services\EmailMensalidadeService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class MensalidadeSecretariaResource extends Resource
{
    protected static ?string $model = Mensalidade::class;
    protected static ?string $modelLabel = 'Mensalidade';
    protected static ?string $pluralModelLabel = 'Mensalidades';
    protected static ?string $navigationLabel = 'Mensalidades';
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = '💰 Financeiro';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados da Mensalidade')
                    ->description('Informações da cobrança')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Select::make('matricula_id')
                            ->label('Matrícula')
                            ->options(function () {
                                return Matricula::query()
                                    ->whereNotNull('numero_matricula')
                                    ->where('numero_matricula', '!=', '')
                                    ->with(['user', 'curso'])
                                    ->get()
                                    ->mapWithKeys(fn ($matricula) => [
                                        $matricula->id => "{$matricula->numero_matricula} - {$matricula->user->name} ({$matricula->curso->titulo})"
                                    ])
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn ($record) => $record !== null)
                            ->columnSpan(2),

                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('valor')
                                ->label('Valor')
                                ->numeric()
                                ->prefix('R$')
                                ->required()
                                ->step(0.01)
                                ->default(0.00)
                                ->minValue(0.01),

                            Forms\Components\DatePicker::make('data_vencimento')
                                ->label('Data de Vencimento')
                                ->required()
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->prefixIcon('heroicon-o-calendar')
                                ->default(now()->addMonth()),

                            Forms\Components\Select::make('status')
                                ->label('Status')
                                ->options([
                                    'Pendente' => '⏳ Pendente',
                                    'Pago' => '✅ Pago',
                                    'Atrasado' => '🔴 Atrasado',
                                    'Cancelado' => '❌ Cancelado',
                                ])
                                ->default('Pendente')
                                ->required()
                                ->native(false)
                                ->live(),
                        ]),

                        Forms\Components\Textarea::make('observacao')
                            ->label('Observação')
                            ->rows(2)
                            ->maxLength(500)
                            ->placeholder('Informações adicionais sobre esta mensalidade...')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Informações de Pagamento')
                    ->description('Dados do pagamento realizado')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Forms\Components\DatePicker::make('data_pagamento')
                            ->label('Data do Pagamento')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->visible(fn (Forms\Get $get) => $get('status') === 'Pago')
                            ->required(fn (Forms\Get $get) => $get('status') === 'Pago'),

                        Forms\Components\Select::make('metodo_pagamento')
                            ->label('Forma de Pagamento')
                            ->options([
                                'dinheiro' => '💵 Dinheiro',
                                'pix' => '📱 PIX',
                                'cartao_credito' => '💳 Cartão de Crédito',
                                'cartao_debito' => '💳 Cartão de Débito',
                                'boleto' => '📄 Boleto',
                                'bolbradesco' => '📄 Boleto Bradesco',
                                'transferencia' => '🏦 Transferência',
                                'mercadopago' => '🔵 Mercado Pago (Auto)',
                            ])
                            ->native(false)
                            ->visible(fn (Forms\Get $get) => $get('status') === 'Pago')
                            ->required(fn (Forms\Get $get) => $get('status') === 'Pago')
                            ->live(),

                        Forms\Components\FileUpload::make('comprovante')
                            ->label('Comprovante de Pagamento')
                            ->directory('comprovantes_pagamento')
                            ->image()
                            ->imageEditor()
                            ->maxSize(5120)
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->helperText('Anexe o comprovante do PIX, foto do cartão ou recibo')
                            ->visible(fn (Forms\Get $get) => 
                                $get('status') === 'Pago' && 
                                in_array($get('metodo_pagamento'), ['pix', 'transferencia'])
                            )
                            ->columnSpan(2)
                            ->downloadable()
                            ->openable(),

                        Forms\Components\TextInput::make('transacao_id')
                            ->label('ID da Transação')
                            ->placeholder('ID do Mercado Pago ou outro gateway')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => filled($record?->transacao_id)),

                        Forms\Components\TextInput::make('linha_digitavel')
                            ->label('Linha Digitável (Boleto)')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => filled($record?->linha_digitavel))
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('pix_copia_cola')
                            ->label('PIX Copia e Cola')
                            ->rows(3)
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => filled($record?->pix_copia_cola))
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(fn ($record) => $record === null || $record->status !== 'Pago'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('matricula.numero_matricula')
                    ->label('Nº Matrícula')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->tooltip('Clique para copiar'),

                Tables\Columns\TextColumn::make('matricula.user.name')
                    ->label('Aluno')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->matricula?->curso?->titulo)
                    ->icon('heroicon-m-user')
                    ->limit(30)
                    ->wrap(),

                Tables\Columns\TextColumn::make('valor')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable()
                    ->weight('bold')
                    ->color(fn ($record) => 
                        $record->status === 'Pago' ? 'success' : 
                        ($record->status === 'Atrasado' ? 'danger' : 'gray')
                    ),

                Tables\Columns\TextColumn::make('data_vencimento')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->description(fn ($record) => 
                        $record->status !== 'Pago' && $record->data_vencimento->isPast() 
                            ? (int) $record->data_vencimento->diffInDays(now()) . ' dias de atraso' 
                            : null
                    )
                    ->icon('heroicon-m-calendar')
                    ->color(fn ($record) => 
                        $record->status !== 'Pago' && $record->data_vencimento->isPast() 
                            ? 'danger' 
                            : null
                    ),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'Pago' => 'success',
                        'Pendente' => 'warning',
                        'Atrasado' => 'danger',
                        'Cancelado' => 'gray',
                        default => 'info',
                    })
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'Pago' => '✅ Pago',
                        'Pendente' => '⏳ Pendente',
                        'Atrasado' => '🔴 Atrasado',
                        'Cancelado' => '❌ Cancelado',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('metodo_pagamento')
                    ->label('Método')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'dinheiro' => '💵',
                        'pix' => '📱',
                        'cartao_credito' => '💳',
                        'cartao_debito' => '💳',
                        'boleto' => '📄',
                        'bolbradesco' => '📄',
                        'transferencia' => '🏦',
                        'mercadopago' => '🔵',
                        default => '-',
                    })
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('data_pagamento')
                    ->label('Data Pagto')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('comprovante')
                    ->label('Comprovante')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-check')
                    ->falseIcon('heroicon-o-document')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn ($record) => 
                        $record->comprovante ? 'Comprovante anexado' : 'Sem comprovante'
                    )
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'Pago' => '✅ Pago',
                        'Pendente' => '⏳ Pendente',
                        'Atrasado' => '🔴 Atrasado',
                        'Cancelado' => '❌ Cancelado',
                    ])
                    ->multiple()
                    ->native(false),

                Tables\Filters\SelectFilter::make('metodo_pagamento')
                    ->label('Forma de Pagamento')
                    ->options([
                        'dinheiro' => '💵 Dinheiro',
                        'pix' => '📱 PIX',
                        'cartao_credito' => '💳 Cartão Crédito',
                        'cartao_debito' => '💳 Cartão Débito',
                        'boleto' => '📄 Boleto',
                        'bolbradesco' => '📄 Boleto Bradesco',
                        'transferencia' => '🏦 Transferência',
                        'mercadopago' => '🔵 Mercado Pago',
                    ])
                    ->multiple()
                    ->native(false),

                Tables\Filters\Filter::make('vencimento')
                    ->label('Período de Vencimento')
                    ->form([
                        Forms\Components\DatePicker::make('vencimento_de')
                            ->label('De')
                            ->native(false),
                        Forms\Components\DatePicker::make('vencimento_ate')
                            ->label('Até')
                            ->native(false),
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
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['vencimento_de'] ?? null) {
                            $indicators[] = 'De: ' . \Carbon\Carbon::parse($data['vencimento_de'])->format('d/m/Y');
                        }
                        if ($data['vencimento_ate'] ?? null) {
                            $indicators[] = 'Até: ' . \Carbon\Carbon::parse($data['vencimento_ate'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),

                Tables\Filters\Filter::make('apenas_vencidas')
                    ->label('Apenas Vencidas')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('data_vencimento', '<', now())
                              ->where('status', '!=', 'Pago')
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('sem_comprovante')
                    ->label('Sem Comprovante')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('status', 'Pago')
                              ->whereNull('comprovante')
                    )
                    ->toggle(),

                Tables\Filters\SelectFilter::make('matricula_id')
                    ->label('Matrícula')
                    ->relationship('matricula', 'numero_matricula')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\Filter::make('aluno')
                    ->label('Buscar por Aluno')
                    ->form([
                        Forms\Components\TextInput::make('aluno_nome')
                            ->label('Nome do Aluno')
                            ->placeholder('Digite o nome...')
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['aluno_nome'],
                            fn (Builder $query, $nome): Builder => 
                                $query->whereHas('matricula.user', fn ($q) => 
                                    $q->where('name', 'like', "%{$nome}%")
                                )
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['aluno_nome']) {
                            return null;
                        }
                        return 'Aluno: ' . $data['aluno_nome'];
                    }),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->persistFiltersInSession()
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    
                    Tables\Actions\EditAction::make()
                        ->color('warning'),

                    // ===== GERAR PIX =====
                    Tables\Actions\Action::make('gerar_pix')
                        ->label('Gerar PIX')
                        ->icon('heroicon-o-qr-code')
                        ->color('success')
                        ->visible(fn ($record) => 
                            !$record->transacao_id && 
                            $record->status !== 'Pago' && 
                            $record->status !== 'Cancelado'
                        )
                        ->requiresConfirmation()
                        ->modalHeading('Gerar Pagamento PIX')
                        ->modalDescription('Será gerado um código PIX e QR Code')
                        ->form([
                            Forms\Components\Checkbox::make('enviar_whatsapp')
                                ->label('📱 Enviar no WhatsApp')
                                ->default(true),
                            Forms\Components\Checkbox::make('enviar_email')
                                ->label('📧 Enviar por Email')
                                ->default(true),
                        ])
                        ->action(function (Mensalidade $record, array $data) {
                            try {
                                $mpService = app(MercadoPagoService::class);
                                $mpService->gerarPix($record);

                                if ($data['enviar_whatsapp']) {
                                    $whatsapp = app(WhatsAppService::class);
                                    $telefone = $record->matricula->user->telefone ?? '';
                                    if ($telefone) {
                                        $whatsapp->enviarPixQRCode($telefone, $record->pix_qr_base64, $record->pix_copia_cola);
                                    }
                                }

                                if ($data['enviar_email']) {
                                    $emailService = app(EmailMensalidadeService::class);
                                    $emailService->enviarPix($record);
                                }

                                Notification::make()
                                    ->title('✅ PIX gerado com sucesso!')
                                    ->body('QR Code criado e enviado ao aluno.')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('❌ Erro ao gerar PIX')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();

                                Log::error('Erro ao gerar PIX', [
                                    'mensalidade_id' => $record->id,
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }),

                    // ===== GERAR BOLETO =====
                    Tables\Actions\Action::make('gerar_boleto')
                        ->label('Gerar Boleto')
                        ->icon('heroicon-o-document-text')
                        ->color('warning')
                        ->visible(fn ($record) => 
                            !$record->transacao_id && 
                            $record->status !== 'Pago' && 
                            $record->status !== 'Cancelado'
                        )
                        ->requiresConfirmation()
                        ->modalHeading('Gerar Boleto Bancário')
                        ->modalDescription('Será gerado um boleto bancário')
                        ->form([
                            Forms\Components\Checkbox::make('enviar_whatsapp')
                                ->label('📱 Enviar no WhatsApp')
                                ->default(true),
                            Forms\Components\Checkbox::make('enviar_email')
                                ->label('📧 Enviar por Email')
                                ->default(true),
                        ])
                        ->action(function (Mensalidade $record, array $data) {
                            try {
                                $mpService = app(MercadoPagoService::class);
                                $mpService->gerarBoleto($record);

                                if ($data['enviar_whatsapp']) {
                                    $whatsapp = app(WhatsAppService::class);
                                    $telefone = $record->matricula->user->telefone ?? '';
                                    if ($telefone) {
                                        $mensagem = "📄 *Boleto Gerado*\n\n";
                                        $mensagem .= "Olá, {$record->matricula->user->name}!\n\n";
                                        $mensagem .= "Seu boleto foi gerado:\n";
                                        $mensagem .= "💰 Valor: R$ " . number_format($record->valor, 2, ',', '.') . "\n";
                                        $mensagem .= "📅 Vencimento: {$record->data_vencimento->format('d/m/Y')}\n\n";
                                        $mensagem .= "🔗 Link: {$record->link_boleto}\n\n";
                                        $mensagem .= "📋 Linha Digitável:\n`{$record->linha_digitavel}`";
                                        $whatsapp->enviarMensagem($telefone, $mensagem);
                                    }
                                }

                                if ($data['enviar_email']) {
                                    $emailService = app(EmailMensalidadeService::class);
                                    $emailService->enviarBoleto($record);
                                }

                                Notification::make()
                                    ->title('✅ Boleto gerado com sucesso!')
                                    ->body('Boleto criado e enviado ao aluno.')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('❌ Erro ao gerar Boleto')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();

                                Log::error('Erro ao gerar Boleto', [
                                    'mensalidade_id' => $record->id,
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }),

                    // ===== VER PIX =====
                    Tables\Actions\Action::make('ver_pix')
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

                    // ===== VER BOLETO =====
                    Tables\Actions\Action::make('ver_boleto')
                        ->label('Ver Boleto')
                        ->icon('heroicon-o-document-text')
                        ->color('warning')
                        ->visible(fn ($record) => 
                            in_array($record->metodo_pagamento, ['boleto', 'bolbradesco']) && 
                            $record->link_boleto
                        )
                        ->url(fn ($record) => $record->link_boleto)
                        ->openUrlInNewTab(),

                    // ===== DAR BAIXA =====
                    Tables\Actions\Action::make('dar_baixa')
                        ->label('Dar Baixa')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => $record->status !== 'Pago')
                        ->form([
                            Forms\Components\DatePicker::make('data_pagamento')
                                ->label('Data do Pagamento')
                                ->default(now())
                                ->required()
                                ->native(false)
                                ->displayFormat('d/m/Y'),

                            Forms\Components\Select::make('metodo_pagamento')
                                ->label('Forma de Pagamento')
                                ->options([
                                    'dinheiro' => '💵 Dinheiro (Gera Recibo)',
                                    'pix' => '📱 PIX',
                                    'cartao_credito' => '💳 Cartão de Crédito',
                                    'cartao_debito' => '💳 Cartão de Débito',
                                    'boleto' => '📄 Boleto',
                                    'transferencia' => '🏦 Transferência',
                                ])
                                ->required()
                                ->native(false)
                                ->live(),

                            Forms\Components\FileUpload::make('comprovante')
                                ->label('Comprovante')
                                ->directory('comprovantes_pagamento')
                                ->image()
                                ->imageEditor()
                                ->maxSize(5120)
                                ->acceptedFileTypes(['image/*', 'application/pdf'])
                                ->helperText('Obrigatório para PIX e Transferência')
                                ->required(fn (Forms\Get $get) => 
                                    in_array($get('metodo_pagamento'), ['pix', 'transferencia'])
                                )
                                ->visible(fn (Forms\Get $get) => 
                                    in_array($get('metodo_pagamento'), ['pix', 'transferencia'])
                                ),

                            Forms\Components\Textarea::make('observacao')
                                ->label('Observação')
                                ->rows(2)
                                ->placeholder('Informações adicionais...'),

                            Forms\Components\Checkbox::make('enviar_comprovante_whatsapp')
                                ->label('📱 Enviar comprovante no WhatsApp')
                                ->default(true),

                            Forms\Components\Checkbox::make('enviar_comprovante_email')
                                ->label('📧 Enviar comprovante por Email')
                                ->default(true),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update([
                                'status' => 'Pago',
                                'data_pagamento' => $data['data_pagamento'],
                                'metodo_pagamento' => $data['metodo_pagamento'],
                                'comprovante' => $data['comprovante'] ?? null,
                                'observacao' => $data['observacao'] ?? $record->observacao,
                            ]);

                            // Enviar notificações se solicitado
                            if ($data['enviar_comprovante_whatsapp'] ?? false) {
                                try {
                                    $whatsapp = app(WhatsAppService::class);
                                    $telefone = $record->matricula->user->telefone ?? '';
                                    if ($telefone) {
                                        $mensagem = "✅ *Pagamento Confirmado*\n\n";
                                        $mensagem .= "Olá, {$record->matricula->user->name}!\n\n";
                                        $mensagem .= "Confirmamos o recebimento do seu pagamento:\n";
                                        $mensagem .= "💰 Valor: R$ " . number_format($record->valor, 2, ',', '.') . "\n";
                                        $mensagem .= "📅 Data: {$record->data_pagamento->format('d/m/Y')}\n";
                                        $mensagem .= "💳 Método: " . match($record->metodo_pagamento) {
                                            'dinheiro' => 'Dinheiro',
                                            'pix' => 'PIX',
                                            'cartao_credito' => 'Cartão de Crédito',
                                            'cartao_debito' => 'Cartão de Débito',
                                            'boleto' => 'Boleto',
                                            'transferencia' => 'Transferência',
                                            default => $record->metodo_pagamento
                                        } . "\n\n";
                                        $mensagem .= "Obrigado! ✅";
                                        $whatsapp->enviarMensagem($telefone, $mensagem);
                                    }
                                } catch (\Exception $e) {
                                    Log::error('Erro ao enviar WhatsApp', ['error' => $e->getMessage()]);
                                }
                            }

                            if ($data['enviar_comprovante_email'] ?? false) {
                                try {
                                    $emailService = app(EmailMensalidadeService::class);
                                    $emailService->enviarConfirmacaoPagamento($record);
                                } catch (\Exception $e) {
                                    Log::error('Erro ao enviar Email', ['error' => $e->getMessage()]);
                                }
                            }

                            Notification::make()
                                ->title('✅ Pagamento Confirmado!')
                                ->body("Mensalidade de {$record->matricula->user->name} marcada como paga.")
                                ->success()
                                ->send();
                        }),

                    // ===== CANCELAR PAGAMENTO =====
                    Tables\Actions\Action::make('cancelar_pagamento')
                        ->label('Cancelar Pagamento MP')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn ($record) => 
                            $record->transacao_id && 
                            $record->status !== 'Pago' && 
                            $record->status !== 'Cancelado'
                        )
                        ->requiresConfirmation()
                        ->modalHeading('Cancelar Pagamento')
                        ->modalDescription('Deseja cancelar este pagamento no Mercado Pago?')
                        ->action(function (Mensalidade $record) {
                            try {
                                $mpService = app(MercadoPagoService::class);
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

                    // ===== WHATSAPP =====
                    Tables\Actions\Action::make('whatsapp')
                        ->label('WhatsApp')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('success')
                        ->url(fn ($record) => 
                            "https://api.whatsapp.com/send?phone=55" . 
                            preg_replace('/[^0-9]/', '', $record->matricula->user->telefone ?? '')
                        )
                        ->openUrlInNewTab()
                        ->visible(fn ($record) => filled($record->matricula->user->telefone ?? null)),

                    Tables\Actions\DeleteAction::make(),
                ])
                ->label('Ações')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('marcar_como_pago')
                        ->label('Dar Baixa em Lote')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\DatePicker::make('data_pagamento')
                                ->label('Data do Pagamento')
                                ->default(now())
                                ->required()
                                ->native(false),

                            Forms\Components\Select::make('metodo_pagamento')
                                ->label('Forma de Pagamento')
                                ->options([
                                    'dinheiro' => '💵 Dinheiro',
                                    'pix' => '📱 PIX',
                                    'cartao_credito' => '💳 Crédito',
                                    'cartao_debito' => '💳 Débito',
                                    'boleto' => '📄 Boleto',
                                    'transferencia' => '🏦 Transferência',
                                ])
                                ->required()
                                ->native(false),
                        ])
                        ->action(function ($records, array $data) {
                            $records->each->update([
                                'status' => 'Pago',
                                'data_pagamento' => $data['data_pagamento'],
                                'metodo_pagamento' => $data['metodo_pagamento'],
                            ]);

                            Notification::make()
                                ->title('Baixa em lote realizada!')
                                ->body(count($records) . ' mensalidades marcadas como pagas.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('gerar_relatorio')
                        ->label('Gerar Relatório')
                        ->icon('heroicon-o-document-chart-bar')
                        ->color('primary')
                        ->action(function ($records) {
                            $ids = $records->pluck('id')->toArray();
                            return redirect()->route('mensalidades.relatorio.pdf', ['ids' => implode(',', $ids)]);
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('data_vencimento', 'desc')
            ->poll('30s')
            ->striped()
            ->emptyStateHeading('Nenhuma mensalidade encontrada')
            ->emptyStateDescription('As mensalidades aparecerão aqui')
            ->emptyStateIcon('heroicon-o-banknotes');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMensalidadesSecretaria::route('/'),
            'create' => Pages\CreateMensalidadeSecretaria::route('/create'),
            'edit' => Pages\EditMensalidadeSecretaria::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            MensalidadeSecretariaResource\Widgets\MensalidadeFinanceiraStats::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $atrasadas = static::getModel()::where('status', 'Atrasado')->count();
        return $atrasadas > 0 ? (string)$atrasadas : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}