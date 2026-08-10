<?php

namespace App\Filament\Secretario\Resources;

use App\Filament\Secretario\Resources\MovimentacaoFinanceiraResource\Pages;
use App\Models\MovimentacaoFinanceira;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class MovimentacaoFinanceiraResource extends Resource
{
    protected static ?string $model = MovimentacaoFinanceira::class;
    protected static ?string $modelLabel = 'Movimentação';
    protected static ?string $pluralModelLabel = 'Movimentações Financeiras';
    protected static ?string $navigationLabel = 'Movimentações';
    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationGroup = '💰 Financeiro';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados da Movimentação')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\Select::make('tipo')
                                ->label('Tipo')
                                ->options([
                                    'entrada' => '⬇️ Entrada (Receita)',
                                    'saida' => '⬆️ Saída (Despesa)',
                                ])
                                ->required()
                                ->native(false)
                                ->live()
                                ->afterStateUpdated(fn ($state, Forms\Set $set) => 
                                    $state === 'entrada' ? $set('beneficiario', null) : $set('pagador', null)
                                ),

                            Forms\Components\Select::make('conta_id')
                                ->label('Conta')
                                ->relationship('conta', 'nome')
                                ->required()
                                ->native(false)
                                ->preload()
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('nome')
                                        ->required()
                                        ->label('Nome da Conta'),
                                    Forms\Components\TextInput::make('saldo_inicial')
                                        ->numeric()
                                        ->default(0)
                                        ->prefix('R$')
                                        ->label('Saldo Inicial'),
                                ]),

                            Forms\Components\Select::make('categoria_id')
                                ->label('Categoria')
                                ->relationship('categoria', 'nome', fn (Builder $query, Forms\Get $get) => 
                                    $query->where('tipo', $get('tipo') === 'entrada' ? 'Receita' : 'Despesa')
                                )
                                ->required()
                                ->native(false)
                                ->preload()
                                ->searchable(),
                        ]),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\DatePicker::make('data_movimentacao')
                                ->label('Data')
                                ->required()
                                ->default(now())
                                ->native(false)
                                ->displayFormat('d/m/Y'),

                            Forms\Components\TextInput::make('valor')
                                ->label('Valor')
                                ->required()
                                ->numeric()
                                ->prefix('R$')
                                ->step(0.01)
                                ->minValue(0.01),
                        ]),

                        Forms\Components\TextInput::make('descricao')
                            ->label('Descrição')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ex: Pagamento professor João - Janeiro/2025')
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('beneficiario')
                                ->label('Beneficiário (Quem Recebe)')
                                ->visible(fn (Forms\Get $get) => $get('tipo') === 'saida')
                                ->placeholder('Nome de quem receberá o pagamento'),

                            Forms\Components\TextInput::make('pagador')
                                ->label('Pagador (Quem Paga)')
                                ->visible(fn (Forms\Get $get) => $get('tipo') === 'entrada')
                                ->placeholder('Nome de quem está pagando'),
                        ]),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'efetivado' => '✅ Efetivado',
                                'pendente' => '⏳ Pendente',
                                'cancelado' => '❌ Cancelado',
                            ])
                            ->default('efetivado')
                            ->required()
                            ->native(false),

                        Forms\Components\FileUpload::make('comprovante')
                            ->label('Comprovante')
                            ->directory('comprovantes_financeiros')
                            ->image()
                            ->imageEditor()
                            ->maxSize(5120)
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->downloadable()
                            ->openable()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('observacoes')
                            ->label('Observações')
                            ->rows(3)
                            ->placeholder('Informações adicionais sobre esta movimentação...')
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('data_movimentacao')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'entrada' => '⬇️ Entrada',
                        'saida' => '⬆️ Saída',
                    })
                    ->color(fn (string $state): string => match($state) {
                        'entrada' => 'success',
                        'saida' => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('descricao')
                    ->label('Descrição')
                    ->searchable()
                    ->limit(50)
                    ->wrap(),

                Tables\Columns\TextColumn::make('categoria.nome')
                    ->label('Categoria')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('conta.nome')
                    ->label('Conta')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('valor')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable()
                    ->weight('bold')
                    ->color(fn ($record) => $record->tipo === 'entrada' ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'efetivado' => '✅ Efetivado',
                        'pendente' => '⏳ Pendente',
                        'cancelado' => '❌ Cancelado',
                    })
                    ->color(fn (string $state): string => match($state) {
                        'efetivado' => 'success',
                        'pendente' => 'warning',
                        'cancelado' => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('comprovante')
                    ->label('Comprovante')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-check')
                    ->falseIcon('heroicon-o-document')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('criadoPor.name')
                    ->label('Criado Por')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->options([
                        'entrada' => '⬇️ Entradas',
                        'saida' => '⬆️ Saídas',
                    ])
                    ->native(false),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'efetivado' => '✅ Efetivado',
                        'pendente' => '⏳ Pendente',
                        'cancelado' => '❌ Cancelado',
                    ])
                    ->native(false),

                Tables\Filters\SelectFilter::make('conta_id')
                    ->label('Conta')
                    ->relationship('conta', 'nome')
                    ->native(false)
                    ->preload(),

                Tables\Filters\SelectFilter::make('categoria_id')
                    ->label('Categoria')
                    ->relationship('categoria', 'nome')
                    ->native(false)
                    ->preload()
                    ->searchable(),

                Tables\Filters\Filter::make('data')
                    ->form([
                        Forms\Components\DatePicker::make('data_de')
                            ->label('De')
                            ->native(false),
                        Forms\Components\DatePicker::make('data_ate')
                            ->label('Até')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['data_de'], fn ($q, $date) => $q->whereDate('data_movimentacao', '>=', $date))
                            ->when($data['data_ate'], fn ($q, $date) => $q->whereDate('data_movimentacao', '<=', $date));
                    }),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->persistFiltersInSession()
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    
                    Tables\Actions\EditAction::make()
                        ->color('warning')
                        ->before(function ($record) {
                            // Salvar dados anteriores para auditoria
                            session(['dados_anteriores_' . $record->id => $record->toArray()]);
                        })
                        ->after(function ($record) {
                            $dadosAnteriores = session('dados_anteriores_' . $record->id);
                            $record->registrarAuditoria('edicao', $dadosAnteriores);
                            session()->forget('dados_anteriores_' . $record->id);
                            
                            Notification::make()
                                ->title('Movimentação Alterada')
                                ->body('Alteração registrada no histórico de auditoria.')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('ver_auditoria')
                        ->label('Ver Histórico')
                        ->icon('heroicon-o-clock')
                        ->color('info')
                        ->modalHeading('Histórico de Alterações')
                        ->modalContent(fn ($record) => view('filament.modals.auditoria-movimentacao', [
                            'movimentacao' => $record,
                            'auditorias' => $record->auditorias()->with('usuario')->latest()->get(),
                        ]))
                        ->modalSubmitActionLabel('Fechar')
                        ->modalCancelAction(false),

                    Tables\Actions\DeleteAction::make()
                        ->before(function ($record) {
                            $dadosAnteriores = $record->toArray();
                            $record->registrarAuditoria('cancelamento', $dadosAnteriores);
                        }),
                ])
                ->label('Ações')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('data_movimentacao', 'desc')
            ->poll('30s')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMovimentacaoFinanceiras::route('/'),
            'create' => Pages\CreateMovimentacaoFinanceira::route('/create'),
            'edit' => Pages\EditMovimentacaoFinanceira::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $pendentes = static::getModel()::where('status', 'pendente')->count();
        return $pendentes > 0 ? (string)$pendentes : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}