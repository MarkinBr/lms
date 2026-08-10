<?php

namespace App\Filament\Aluno\Resources;

use App\Filament\Aluno\Resources\FinanceiroResource\Pages;
use App\Models\Mensalidade; // <--- Model Correto
use App\Services\MercadoPagoService; // <--- Serviço de Pagamento
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FinanceiroResource extends Resource
{
    protected static ?string $model = Mensalidade::class; // <--- Aponta para a tabela mensalidades
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Financeiro';
    protected static ?string $modelLabel = 'mensalidade';
    protected static ?string $pluralModelLabel = 'Mensalidades';
    protected static ?string $navigationGroup = 'Financeiro';
    protected static ?int $navigationSort = 3;

    // Filtra APENAS as mensalidades do aluno logado
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('matricula', function ($query) {
                $query->where('user_id', Auth::id());
            });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalhes da Mensalidade')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                // Mostra o nome do curso vindo da relação
                                Forms\Components\TextInput::make('curso_desc')
                                    ->label('Curso / Turma')
                                    ->disabled()
                                    ->dehydrated(false) // Não tenta salvar no banco
                                    ->formatStateUsing(function ($record) {
                                        // Tenta pegar o Curso (Novo)
                                        if ($record->matricula && $record->matricula->curso) {
                                            return $record->matricula->curso->titulo;
                                        }
                                        // Se não tiver curso, tenta pegar a Turma (Antigo - se o model tiver a relação)
                                        if ($record->matricula && $record->matricula->turma) {
                                            return $record->matricula->turma->nome;
                                        }
                                        return 'Curso não identificado';
                                    }),

                                Forms\Components\TextInput::make('valor')
                                    ->label('Valor')
                                    ->prefix('R$')
                                    ->numeric()
                                    ->disabled(),

                                Forms\Components\DatePicker::make('data_vencimento')
                                    ->label('Vencimento')
                                    ->displayFormat('d/m/Y')
                                    ->disabled(),
                            ]),

                        Forms\Components\Placeholder::make('status_visual')
                            ->label('Status')
                            ->content(fn ($record) => new \Illuminate\Support\HtmlString(
                                match($record?->status) {
                                    'Pago' => '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800">✓ Pago</span>',
                                    'Atrasado' => '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-red-100 text-red-800">⚠ Atrasado</span>',
                                    'Pendente' => '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-800">⏳ Pendente</span>',
                                    'Cancelado' => '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-gray-100 text-gray-800">🚫 Cancelado</span>',
                                    default => '-',
                                }
                            )),

                        Forms\Components\Textarea::make('observacao')
                            ->label('Descrição / Parcela')
                            ->disabled()
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
                
                // Área PIX (Visível apenas se tiver chave gerada)
                Forms\Components\Section::make('Pagamento PIX')
                    ->schema([
                        Forms\Components\Textarea::make('pix_copia_cola')
                            ->label('Código PIX Copia e Cola')
                            ->disabled()
                            ->rows(3)
                            ->columnSpanFull()
                            ->hint('Copie este código e cole no app do seu banco.'),
                    ])
                    ->visible(fn ($record) => $record && $record->pix_copia_cola && $record->status !== 'Pago')
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('matricula.turma.nome')
                    ->label('Curso')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(25)
                    ->description(fn ($record) => $record->observacao), // Ex: Parcela 1/12

                Tables\Columns\TextColumn::make('data_vencimento')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => 
                        $record->status == 'Pago' ? 'success' :
                        ($record->data_vencimento < now() ? 'danger' : 
                        ($record->data_vencimento->diffInDays(now()) <= 3 ? 'warning' : 'gray'))
                    ),

                Tables\Columns\TextColumn::make('valor')
                    ->label('Valor')
                    ->money('BRL')
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'Pago',
                        'warning' => 'Pendente',
                        'danger' => 'Atrasado',
                        'gray' => 'Cancelado',
                    ]),

                Tables\Columns\TextColumn::make('metodo_pagamento')
                    ->label('Forma')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pix' => 'PIX',
                        'bolbradesco' => 'Boleto',
                        default => $state ? strtoupper($state) : '-'
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('data_vencimento', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Pago' => 'Pago',
                        'Pendente' => 'Pendente',
                        'Atrasado' => 'Atrasado',
                    ])
                    ->multiple(),
                
                Tables\Filters\Filter::make('vencidas')
                    ->label('Apenas Vencidas')
                    ->query(fn (Builder $query) => 
                        $query->where('status', '!=', 'Pago')
                              ->where('data_vencimento', '<', now())
                    ),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->color('info'),

                    // === AÇÃO: GERAR PIX (Igual ao Admin) ===
                    Tables\Actions\Action::make('gerarPix')
                        ->label('Gerar PIX')
                        ->icon('heroicon-o-qr-code')
                        ->color('success')
                        ->visible(fn ($record) => !$record->transacao_id && $record->status !== 'Pago')
                        ->requiresConfirmation()
                        ->modalHeading('Pagar com PIX')
                        ->modalDescription('Deseja gerar o código PIX para pagamento agora?')
                        ->action(function (Mensalidade $record) {
                            try {
                                $mpService = new MercadoPagoService();
                                $resultado = $mpService->gerarPix($record);

                                Notification::make()
                                    ->title('PIX gerado com sucesso!')
                                    ->success()
                                    ->body('O código PIX foi gerado. Clique em "Ver PIX" para pagar.')
                                    ->send();

                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Erro ao gerar PIX')
                                    ->danger()
                                    ->body($e->getMessage())
                                    ->send();

                                Log::error('Erro PIX Aluno', ['id' => $record->id, 'erro' => $e->getMessage()]);
                            }
                        }),

                    // === AÇÃO: GERAR BOLETO (Igual ao Admin) ===
                    Tables\Actions\Action::make('gerarBoleto')
                        ->label('Gerar Boleto')
                        ->icon('heroicon-o-document-text')
                        ->color('warning')
                        ->visible(fn ($record) => !$record->transacao_id && $record->status !== 'Pago')
                        ->requiresConfirmation()
                        ->modalHeading('Gerar Boleto')
                        ->action(function (Mensalidade $record) {
                            try {
                                $mpService = new MercadoPagoService();
                                $resultado = $mpService->gerarBoleto($record);

                                Notification::make()
                                    ->title('Boleto gerado!')
                                    ->success()
                                    ->send();

                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Erro ao gerar boleto')
                                    ->danger()
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        }),

                    // === AÇÃO: VER PIX (Modal com QR Code) ===
                    Tables\Actions\Action::make('verPix')
                        ->label('Pagar (Ver PIX)')
                        ->icon('heroicon-o-qr-code')
                        ->color('success')
                        ->visible(fn ($record) => $record->metodo_pagamento === 'pix' && $record->pix_copia_cola && $record->status !== 'Pago')
                        ->modalHeading('Pagamento via PIX')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Fechar')
                        ->modalContent(fn ($record) => view('filament.modals.pix-info', ['mensalidade' => $record])),

                    // === AÇÃO: VER BOLETO ===
                    Tables\Actions\Action::make('verBoleto')
                        ->label('Imprimir Boleto')
                        ->icon('heroicon-o-printer')
                        ->color('primary')
                        ->visible(fn ($record) => $record->metodo_pagamento === 'bolbradesco' && $record->link_boleto && $record->status !== 'Pago')
                        ->url(fn ($record) => $record->link_boleto)
                        ->openUrlInNewTab(),

                    // === AÇÃO: BAIXAR COMPROVANTE (Apenas se Pago) ===
                    Tables\Actions\Action::make('comprovante')
                        ->label('Recibo')
                        ->icon('heroicon-o-document-check')
                        ->color('gray')
                        ->visible(fn ($record) => $record->status == 'Pago')
                        ->action(function ($record) {
                            Notification::make()
                                ->title('Download iniciado')
                                ->body('Seu recibo está sendo gerado.')
                                ->success()
                                ->send();
                            // Aqui entraria a lógica de gerar PDF do recibo
                        }),
                ]),
            ])
            ->emptyStateHeading('Tudo em dia!')
            ->emptyStateDescription('Nenhuma mensalidade pendente encontrada.');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFinanceiro::route('/'),
            'view' => Pages\ViewFinanceiro::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        // Badge vermelho se tiver mensalidade atrasada ou pendente vencendo logo
        $alerta = static::getModel()::whereHas('matricula', fn($q) => $q->where('user_id', Auth::id()))
            ->where('status', '!=', 'Pago')
            ->where('data_vencimento', '<=', now()->addDays(5))
            ->count();
        
        return $alerta > 0 ? (string) $alerta : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}