<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PeriodoLetivoResource\Pages;
use App\Models\PeriodoLetivo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Resource administrativo de Períodos Letivos (Issue #57).
 *
 * Resolve o atalho do administrador: o CRUD passa a existir no painel
 * /admin, sem redirecionar para /secretaria. Resource independente —
 * nenhuma herança do PeriodoLetivoSecretariaResource. A autorização
 * continua na PeriodoLetivoPolicy (admin/secretário ativos).
 */
class PeriodoLetivoResource extends Resource
{
    protected static ?string $model = PeriodoLetivo::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Períodos Letivos';

    protected static ?string $modelLabel = 'Período Letivo';

    protected static ?string $pluralModelLabel = 'Períodos Letivos';

    protected static ?string $navigationGroup = 'Acadêmico';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nome')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Ex: 2026.1'),

                Forms\Components\DatePicker::make('data_inicio')
                    ->label('Início')
                    ->required(),

                Forms\Components\DatePicker::make('data_fim')
                    ->label('Fim')
                    ->required(),

                Forms\Components\Toggle::make('ativo')
                    ->label('Ativo')
                    ->helperText('Somente um período letivo pode estar ativo por vez.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->label('Nome')
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

                Tables\Columns\TextColumn::make('turmas_count')
                    ->counts('turmas')
                    ->label('Turmas')
                    ->alignCenter()
                    ->badge(),

                Tables\Columns\IconColumn::make('ativo')
                    ->label('Ativo')
                    ->boolean(),
            ])
            ->defaultSort('data_inicio', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (PeriodoLetivo $record) => $record->podeSerExcluido()),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePeriodoLetivos::route('/'),
        ];
    }
}
