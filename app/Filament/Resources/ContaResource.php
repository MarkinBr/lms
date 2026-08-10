<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContaResource\Pages;
use App\Filament\Resources\ContaResource\RelationManagers;
use App\Models\Conta;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContaResource extends Resource
{
    protected static ?string $model = Conta::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet'; // Ícone de carteira
    protected static ?string $modelLabel = 'conta';
    protected static ?string $pluralModelLabel = 'contas bancárias / caixas';
    protected static ?string $navigationGroup = 'Financeiro'; // Agrupa no menu

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados da Conta')
                    ->schema([
                        Forms\Components\TextInput::make('nome')
                            ->label('Nome da Conta')
                            ->placeholder('Ex: Caixa Secretaria, Nubank, Mercado Pago')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('tipo')
                            ->options([
                                'Banco' => 'Conta Bancária / Digital',
                                'Caixa' => 'Dinheiro em Espécie (Físico)',
                            ])
                            ->required()
                            ->default('Banco'),

                        Forms\Components\TextInput::make('saldo_inicial')
                            ->label('Saldo de Abertura')
                            ->helperText('Quanto dinheiro tem lá agora?')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0),

                        Forms\Components\Toggle::make('ativa')
                            ->label('Conta Ativa?')
                            ->default(true)
                            ->inline(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Banco' => 'info', // Azul
                        'Caixa' => 'warning', // Amarelo
                    }),

                Tables\Columns\TextColumn::make('saldo_inicial')
                    ->label('Saldo Inicial')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\IconColumn::make('ativa')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContas::route('/'),
            'create' => Pages\CreateConta::route('/create'),
            'edit' => Pages\EditConta::route('/{record}/edit'),
        ];
    }
}