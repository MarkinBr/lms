<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TurmaResource\Pages;
use App\Models\Turma;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TurmaResource extends Resource
{
    protected static ?string $model = Turma::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Acadêmico';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações da Turma')
                    ->schema([
                        Forms\Components\TextInput::make('nome')
                            ->label('Nome da Turma / Curso')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('ano_letivo')
                            ->label('Ano Letivo')
                            ->numeric()
                            ->default(date('Y'))
                            ->required(),

                        Forms\Components\Select::make('turno')
                            ->label('Turno')
                            ->options([
                                '1_sabado' => '1º Sábado',
                                '2_sabado' => '2º Sábado',
                                '3_sabado' => '3º Sábado',
                                '4_sabado' => '4º Sábado',
                            ])
                            ->native(false),

                        Forms\Components\TextInput::make('sala')
                            ->label('Sala')
                            ->placeholder('Ex: Sala 102 ou Zoom'),
                    ])->columns(2),

                Forms\Components\Section::make('Financeiro e Prazos')
                    ->description('Defina o valor total. A mensalidade será calculada automaticamente abaixo.')
                    ->schema([
                        
                        // 1. Valor Total (Reativo)
                        Forms\Components\TextInput::make('valor_total_curso')
                            ->label('Valor Total do Curso (R$)')
                            ->numeric()
                            ->prefix('R$')
                            ->live(onBlur: true) // Atualiza ao sair do campo
                            ->required(),

                        // 2. Parcelas (Reativo)
                        Forms\Components\TextInput::make('qtd_parcelas')
                            ->label('Duração / Parcelas')
                            ->numeric()
                            ->default(12)
                            ->live(onBlur: true) // Atualiza ao sair do campo
                            ->required(),

                        // 3. CAMPO CALCULADO (A Mágica acontece aqui)
                        Forms\Components\Placeholder::make('valor_mensal_calculado')
                            ->label('Valor da Mensalidade (Estimado)')
                            ->content(function (Forms\Get $get) {
                                $total = $get('valor_total_curso');
                                $parcelas = $get('qtd_parcelas');

                                if (!$total || !$parcelas || $parcelas == 0) {
                                    return 'R$ 0,00';
                                }

                                $mensal = $total / $parcelas;
                                return 'R$ ' . number_format($mensal, 2, ',', '.');
                            })
                            ->columnSpanFull() // Ocupa a linha toda para destaque
                            ->extraAttributes(['class' => 'text-xl font-bold text-primary-600']), // Deixa maior e colorido

                        Forms\Components\DatePicker::make('data_inicio')
                            ->label('Data Início')
                            ->displayFormat('d/m/Y'),

                        Forms\Components\DatePicker::make('data_fim')
                            ->label('Data Fim')
                            ->displayFormat('d/m/Y'),

                        Forms\Components\Toggle::make('ativa')
                            ->label('Turma Ativa?')
                            ->default(true)
                            ->inline(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('ano_letivo')->sortable(),
                Tables\Columns\TextColumn::make('turno'),
                // Mostra o valor formatado na lista
                Tables\Columns\TextColumn::make('valor_total_curso')
                    ->money('BRL')
                    ->label('Valor Total'),
                Tables\Columns\BooleanColumn::make('ativa'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListTurmas::route('/'),
            'create' => Pages\CreateTurma::route('/create'),
            'edit' => Pages\EditTurma::route('/{record}/edit'),
        ];
    }
}