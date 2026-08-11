<?php

namespace App\Filament\Secretario\Resources;

use App\Filament\Secretario\Resources\PeriodoLetivoSecretariaResource\Pages;
use App\Models\PeriodoLetivo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PeriodoLetivoSecretariaResource extends Resource
{
    protected static ?string $model = PeriodoLetivo::class;

    protected static ?string $modelLabel = 'Período Letivo';

    protected static ?string $pluralModelLabel = 'Períodos Letivos';

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = '📚 Acadêmico';

    protected static ?int $navigationSort = 3;

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
                Forms\Components\Section::make('Período Letivo')
                    ->schema([
                        Forms\Components\TextInput::make('nome')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\DatePicker::make('data_inicio')
                            ->label('Data de início')
                            ->required()
                            ->native(false),
                        Forms\Components\DatePicker::make('data_fim')
                            ->label('Data final')
                            ->required()
                            ->afterOrEqual('data_inicio')
                            ->native(false),
                        Forms\Components\Toggle::make('ativo')
                            ->label('Ativo')
                            ->helperText('Somente um período letivo pode ficar ativo por vez.'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('data_inicio')
                    ->label('Início')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('data_fim')
                    ->label('Fim')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\IconColumn::make('ativo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('turmas_count')
                    ->counts('turmas')
                    ->label('Turmas')
                    ->badge()
                    ->color('info'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('ativo')
                    ->label('Somente ativos'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (PeriodoLetivo $record) => ! $record->turmas()->exists()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('data_inicio', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePeriodoLetivosSecretaria::route('/'),
        ];
    }
}
