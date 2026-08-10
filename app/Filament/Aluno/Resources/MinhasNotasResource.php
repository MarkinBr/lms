<?php

namespace App\Filament\Aluno\Resources;

use App\Filament\Aluno\Resources\MinhasNotasResource\Pages;
use App\Models\Nota;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MinhasNotasResource extends Resource
{
    protected static ?string $model = Nota::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Minhas Notas';
    protected static ?string $modelLabel = 'nota';
    protected static ?string $pluralModelLabel = 'Notas';
    protected static ?string $navigationGroup = 'Acadêmico';
    protected static ?int $navigationSort = 2;

    // Filtrar apenas as notas do aluno logado
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', Auth::id());
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Visualização de Nota')
                    ->description('Informações acadêmicas registradas')
                    ->schema([
                        Forms\Components\TextInput::make('disciplina')
                            ->label('Disciplina')
                            ->disabled(),

                        Forms\Components\TextInput::make('periodo')
                            ->label('Período')
                            ->disabled(),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('nota_av1')
                                    ->label('AV1')
                                    ->disabled()
                                    ->suffix('/10'),

                                Forms\Components\TextInput::make('nota_av2')
                                    ->label('AV2')
                                    ->disabled()
                                    ->suffix('/10'),

                                Forms\Components\TextInput::make('nota_av3')
                                    ->label('AV3 (Substitutiva)')
                                    ->disabled()
                                    ->suffix('/10'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('media_final')
                                    ->label('Média Final')
                                    ->disabled()
                                    ->suffix('/10')
                                    ->extraAttributes(['class' => 'font-bold text-lg']),

                                Forms\Components\TextInput::make('faltas')
                                    ->label('Faltas')
                                    ->disabled()
                                    ->suffix('faltas'),
                            ]),

                        Forms\Components\Placeholder::make('situacao_visual')
                            ->label('Situação')
                            ->content(fn ($record) => new \Illuminate\Support\HtmlString(
                                match($record?->situacao ?? 'Cursando') {
                                    'Aprovado' => '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800">✓ Aprovado</span>',
                                    'Reprovado' => '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-red-100 text-red-800">✗ Reprovado</span>',
                                    'Reprovado por Falta' => '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-orange-100 text-orange-800">⚠ Reprovado por Falta</span>',
                                    default => '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-800">📚 Cursando</span>',
                                }
                            )),

                        Forms\Components\Textarea::make('observacoes')
                            ->label('Observações do Professor')
                            ->disabled()
                            ->rows(3),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('periodo')
                    ->label('Período')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('disciplina')
                    ->label('Disciplina')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->professor ? "Prof. {$record->professor}" : null),

                Tables\Columns\TextColumn::make('nota_av1')
                    ->label('AV1')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) : '-')
                    ->color(fn ($state) => $state >= 7 ? 'success' : ($state >= 5 ? 'warning' : 'danger'))
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('nota_av2')
                    ->label('AV2')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) : '-')
                    ->color(fn ($state) => $state >= 7 ? 'success' : ($state >= 5 ? 'warning' : 'danger'))
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('nota_av3')
                    ->label('AV3')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) : '-')
                    ->color(fn ($state) => $state >= 7 ? 'success' : ($state >= 5 ? 'warning' : 'danger'))
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('media_final')
                    ->label('Média')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => number_format($state, 1))
                    ->badge()
                    ->color(fn ($state) => $state >= 7 ? 'success' : ($state >= 5 ? 'warning' : 'danger'))
                    ->weight('bold')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Large),

                Tables\Columns\TextColumn::make('faltas')
                    ->label('Faltas')
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($state) => $state > 10 ? 'danger' : ($state > 5 ? 'warning' : 'success'))
                    ->suffix(' faltas'),

                Tables\Columns\TextColumn::make('situacao')
                    ->label('Situação')
                    ->badge()
                    ->alignCenter()
                    ->color(fn (string $state): string => match ($state) {
                        'Aprovado' => 'success',
                        'Reprovado' => 'danger',
                        'Reprovado por Falta' => 'warning',
                        default => 'info',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'Aprovado' => 'heroicon-o-check-circle',
                        'Reprovado' => 'heroicon-o-x-circle',
                        'Reprovado por Falta' => 'heroicon-o-exclamation-triangle',
                        default => 'heroicon-o-clock',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('periodo')
                    ->label('Período')
                    ->options(fn () => 
                        Nota::where('user_id', Auth::id())
                            ->distinct()
                            ->pluck('periodo', 'periodo')
                            ->toArray()
                    )
                    ->multiple(),

                Tables\Filters\SelectFilter::make('situacao')
                    ->label('Situação')
                    ->options([
                        'Aprovado' => 'Aprovado',
                        'Reprovado' => 'Reprovado',
                        'Reprovado por Falta' => 'Reprovado por Falta',
                        'Cursando' => 'Cursando',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('media_baixa')
                    ->label('Média < 7.0')
                    ->query(fn (Builder $query) => $query->where('media_final', '<', 7))
                    ->toggle(),

                Tables\Filters\Filter::make('muitas_faltas')
                    ->label('Muitas Faltas (>10)')
                    ->query(fn (Builder $query) => $query->where('faltas', '>', 10))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detalhes')
                    ->color('info'),
            ])
            ->defaultSort('periodo', 'desc')
            ->poll('60s')
            ->striped()
            ->emptyStateHeading('Nenhuma nota lançada ainda')
            ->emptyStateDescription('As notas das avaliações aparecerão aqui quando forem lançadas pelos professores')
            ->emptyStateIcon('heroicon-o-academic-cap');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMinhasNotas::route('/'),
            'view' => Pages\ViewMinhasNotas::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $notasRecentes = static::getModel()::where('user_id', Auth::id())
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        
        return $notasRecentes > 0 ? (string) $notasRecentes : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}

/*
|--------------------------------------------------------------------------
| Página List - Com Estatísticas e Gráficos
|--------------------------------------------------------------------------
*/

namespace App\Filament\Aluno\Resources\MinhasNotasResource\Pages;

use App\Filament\Aluno\Resources\MinhasNotasResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Auth;
use Filament\Infolists\Components;

class ListMinhasNotas extends ListRecords
{
    protected static string $resource = MinhasNotasResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            MinhasNotasResource\Widgets\BoletimStatsWidget::class,
            MinhasNotasResource\Widgets\GraficoDesempenhoWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 2;
    }
}

/*
|--------------------------------------------------------------------------
| Página View - Detalhes da Nota
|--------------------------------------------------------------------------
*/

namespace App\Filament\Aluno\Resources\MinhasNotasResource\Pages;

use App\Filament\Aluno\Resources\MinhasNotasResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewMinhasNotas extends ViewRecord
{
    protected static string $resource = MinhasNotasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('baixar_boletim')
                ->label('Baixar Boletim')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->action(function () {
                    // Implementar geração de PDF do boletim
                }),
        ];
    }
}

/*
|--------------------------------------------------------------------------
| Widget: Estatísticas do Boletim
|--------------------------------------------------------------------------
*/

namespace App\Filament\Aluno\Resources\MinhasNotasResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use App\Models\Nota;

class BoletimStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $notas = Nota::where('user_id', Auth::id())->get();
        
        $mediaGeral = $notas->avg('media_final');
        $aprovadas = $notas->where('situacao', 'Aprovado')->count();
        $reprovadas = $notas->whereIn('situacao', ['Reprovado', 'Reprovado por Falta'])->count();
        $totalFaltas = $notas->sum('faltas');
        $cursando = $notas->where('situacao', 'Cursando')->count();

        return [
            Stat::make('Média Geral', number_format($mediaGeral, 1))
                ->description('Média de todas as disciplinas')
                ->descriptionIcon('heroicon-o-academic-cap')
                ->color($mediaGeral >= 7 ? 'success' : ($mediaGeral >= 5 ? 'warning' : 'danger'))
                ->chart([6.5, 7.0, 7.5, 7.2, $mediaGeral]),

            Stat::make('Disciplinas Aprovadas', $aprovadas)
                ->description("{$cursando} em andamento")
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Total de Faltas', $totalFaltas)
                ->description('Soma de todas as disciplinas')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($totalFaltas > 20 ? 'danger' : ($totalFaltas > 10 ? 'warning' : 'success')),

            Stat::make('Taxa de Aprovação', 
                $notas->count() > 0 
                    ? round(($aprovadas / ($aprovadas + $reprovadas)) * 100) . '%' 
                    : '0%'
            )
                ->description("{$reprovadas} reprovações")
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('primary'),
        ];
    }
}

/*
|--------------------------------------------------------------------------
| Widget: Gráfico de Desempenho
|--------------------------------------------------------------------------
*/

namespace App\Filament\Aluno\Resources\MinhasNotasResource\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use App\Models\Nota;

class GraficoDesempenhoWidget extends ChartWidget
{
    protected static ?string $heading = 'Desempenho por Período';
    protected static ?string $pollingInterval = null;

    protected function getData(): array
    {
        $notas = Nota::where('user_id', Auth::id())
            ->orderBy('periodo')
            ->get()
            ->groupBy('periodo');

        $periodos = [];
        $medias = [];

        foreach ($notas as $periodo => $notasPeriodo) {
            $periodos[] = $periodo;
            $medias[] = round($notasPeriodo->avg('media_final'), 1);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Média',
                    'data' => $medias,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $periodos,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'max' => 10,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
        ];
    }
}