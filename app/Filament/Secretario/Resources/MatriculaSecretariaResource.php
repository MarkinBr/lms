<?php

namespace App\Filament\Secretario\Resources;

use App\Filament\Secretario\Resources\MatriculaSecretariaResource\Pages;
use App\Models\Matricula;
use App\Models\Declaracao;
use App\Models\Curso;
use App\Models\Turma;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class MatriculaSecretariaResource extends Resource
{
    protected static ?string $model = Matricula::class;
    protected static ?string $modelLabel = 'Matrícula';
    protected static ?string $pluralModelLabel = 'Matrículas';
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = '📚 Acadêmico';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados da Matrícula')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Aluno')
                            ->relationship('user', 'name', fn ($query) => $query->where('nivel', 'aluno'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(2),
                        
                        Forms\Components\Select::make('curso_id')
                            ->label('Curso')
                            ->options(Curso::pluck('titulo', 'id'))
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('turma_id', null)),
                        
                        Forms\Components\Select::make('turma_id')
                            ->label('Turma')
                            ->options(fn (Forms\Get $get): array => 
                                Turma::where('curso_id', $get('curso_id'))
                                    ->pluck('nome', 'id')
                                    ->toArray()
                            )
                            ->required()
                            ->disabled(fn (Forms\Get $get) => !$get('curso_id')),
                        
                        Forms\Components\TextInput::make('numero_matricula')
                            ->label('Número da Matrícula')
                            ->placeholder('Deixe vazio para gerar automaticamente')
                            ->maxLength(20)
                            ->disabled(fn ($record) => $record !== null),
                        
                        Forms\Components\DatePicker::make('data_matricula')
                            ->label('Data da Matrícula')
                            ->default(now())
                            ->required()
                            ->native(false),
                        
                        Forms\Components\TextInput::make('dia_vencimento')
                            ->label('Dia do Vencimento')
                            ->numeric()
                            ->default(10)
                            ->minValue(1)
                            ->maxValue(31)
                            ->required()
                            ->helperText('Dia do mês para vencimento das mensalidades'),
                        
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'ativo' => '✅ Ativo',
                                'trancado' => '⏸️ Trancado',
                                'concluido' => '🎓 Concluído',
                                'cancelado' => '❌ Cancelado',
                            ])
                            ->default('ativo')
                            ->required()
                            ->native(false),
                        
                        Forms\Components\Textarea::make('observacoes')
                            ->label('Observações')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_matricula')
                    ->label('Nº Matrícula')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Aluno')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => route('filament.secretario.resources.matricula-secretarias.edit', $record))
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('curso.titulo')
                    ->label('Curso')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('turma.nome')
                    ->label('Turma')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                
                Tables\Columns\TextColumn::make('data_matricula')
                    ->label('Data Matrícula')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'success' => 'ativo',
                        'warning' => 'trancado',
                        'info' => 'concluido',
                        'danger' => 'cancelado',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'ativo' => '✅ Ativo',
                        'trancado' => '⏸️ Trancado',
                        'concluido' => '🎓 Concluído',
                        'cancelado' => '❌ Cancelado',
                        default => $state,
                    })
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('mensalidades_count')
                    ->label('Mensalidades')
                    ->counts('mensalidades')
                    ->badge()
                    ->color(fn ($state, $record) => 
                        $record->mensalidades()->where('status', 'Atrasado')->count() > 0 
                            ? 'danger' 
                            : 'success'
                    )
                    ->formatStateUsing(function ($state, $record) {
                        $pagas = $record->mensalidades()->where('status', 'Pago')->count();
                        $atrasadas = $record->mensalidades()->where('status', 'Atrasado')->count();
                        
                        if ($atrasadas > 0) {
                            return "{$pagas}/{$state} (🔴 {$atrasadas})";
                        }
                        return "{$pagas}/{$state}";
                    })
                    ->url(fn ($record) => route('filament.secretario.resources.mensalidade-secretarias.index', [
                        'tableFilters' => [
                            'matricula_id' => ['values' => [$record->id]]
                        ]
                    ]))
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Cadastrado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status da Matrícula')
                    ->multiple()
                    ->options([
                        'ativo' => '✅ Ativo',
                        'trancado' => '⏸️ Trancado',
                        'concluido' => '🎓 Concluído',
                        'cancelado' => '❌ Cancelado',
                    ])
                    ->native(false),
                
                Tables\Filters\SelectFilter::make('curso_id')
                    ->label('Curso')
                    ->relationship('curso', 'titulo')
                    ->searchable()
                    ->preload()
                    ->native(false),
                
                Tables\Filters\SelectFilter::make('turma_id')
                    ->label('Turma')
                    ->relationship('turma', 'nome')
                    ->searchable()
                    ->preload()
                    ->native(false),
                
                Tables\Filters\Filter::make('com_mensalidades_atrasadas')
                    ->label('Com Mensalidades Atrasadas')
                    ->query(fn (Builder $query) => 
                        $query->whereHas('mensalidades', fn ($q) => 
                            $q->where('status', 'Atrasado')
                        )
                    )
                    ->toggle(),
                
                Tables\Filters\Filter::make('sem_mensalidades')
                    ->label('Sem Mensalidades Geradas')
                    ->query(fn (Builder $query) => 
                        $query->doesntHave('mensalidades')
                    )
                    ->toggle(),
                
                Tables\Filters\Filter::make('data_matricula')
                    ->form([
                        Forms\Components\DatePicker::make('matriculado_de')
                            ->label('Matriculado De')
                            ->native(false),
                        Forms\Components\DatePicker::make('matriculado_ate')
                            ->label('Até')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['matriculado_de'], 
                                fn (Builder $q, $date) => $q->whereDate('data_matricula', '>=', $date)
                            )
                            ->when($data['matriculado_ate'], 
                                fn (Builder $q, $date) => $q->whereDate('data_matricula', '<=', $date)
                            );
                    }),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->persistFiltersInSession()
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->color('info'),
                
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('mensalidades')
                        ->label('Ver Mensalidades')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->url(fn ($record) => route('filament.secretario.resources.mensalidade-secretarias.index', [
                            'tableFilters' => [
                                'matricula_id' => ['values' => [$record->id]]
                            ]
                        ])),
                    
                    Tables\Actions\Action::make('declaracao')
                        ->label('Gerar Declaração')
                        ->icon('heroicon-o-document-text')
                        ->color('primary')
                        ->action(function ($record) {
                            // Gera código de validação único
                            $codigoValidacao = Declaracao::gerarCodigoValidacao();
                            
                            // Cria registro da declaração
                            $declaracao = Declaracao::create([
                                'matricula_id' => $record->id,
                                'codigo_validacao' => $codigoValidacao,
                                'tipo_documento' => 'declaracao_matricula',
                                'data_emissao' => now(),
                            ]);
                            
                            // Gera hash de integridade
                            $declaracao->update([
                                'hash_documento' => $declaracao->gerarHash()
                            ]);
                            
                            // URL de validação
                            $urlValidacao = route('validacao.validar', ['codigo' => $codigoValidacao]);
                            
                            // Gera QR Code
                            $qrCode = base64_encode(QrCode::format('svg')
                                ->size(300)
                                ->margin(1)
                                ->generate($urlValidacao));
                            
                            // Gera PDF
                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.declaracao-matricula', [
                                'matricula' => $record,
                                'qrCode' => $qrCode,
                                'codigoValidacao' => $codigoValidacao,
                            ]);
                            
                            return response()->streamDownload(function () use ($pdf) {
                                echo $pdf->output();
                            }, "declaracao-matricula-{$record->numero_matricula}.pdf");
                        }),
                    
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Nenhuma matrícula encontrada')
            ->emptyStateDescription('Crie a primeira matrícula clicando no botão abaixo')
            ->emptyStateIcon('heroicon-o-academic-cap');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMatriculasSecretaria::route('/'),
            'create' => Pages\CreateMatriculaSecretaria::route('/create'),
            'edit' => Pages\EditMatriculaSecretaria::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        $atrasadas = Matricula::whereHas('mensalidades', fn ($q) => 
            $q->where('status', 'Atrasado')
        )->count();
        
        return $atrasadas > 0 ? (string)$atrasadas : null;
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}