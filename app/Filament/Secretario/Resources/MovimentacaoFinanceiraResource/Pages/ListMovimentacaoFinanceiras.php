<?php
// ==========================================
// ListMovimentacaoFinanceiras.php
// ==========================================

namespace App\Filament\Secretario\Resources\MovimentacaoFinanceiraResource\Pages;

use App\Filament\Secretario\Resources\MovimentacaoFinanceiraResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Filament\Pages\Concerns\ExposesTableToWidgets;

class ListMovimentacaoFinanceiras extends ListRecords
{
    use ExposesTableToWidgets;
    
    protected static string $resource = MovimentacaoFinanceiraResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nova Movimentação')
                ->icon('heroicon-o-plus'),

            Actions\Action::make('relatorio_mensal')
                ->label('Relatório Mensal')
                ->icon('heroicon-o-document-chart-bar')
                ->color('primary')
                ->form([
                    \Filament\Forms\Components\Select::make('mes')
                        ->label('Mês')
                        ->options([
                            '01' => 'Janeiro',
                            '02' => 'Fevereiro',
                            '03' => 'Março',
                            '04' => 'Abril',
                            '05' => 'Maio',
                            '06' => 'Junho',
                            '07' => 'Julho',
                            '08' => 'Agosto',
                            '09' => 'Setembro',
                            '10' => 'Outubro',
                            '11' => 'Novembro',
                            '12' => 'Dezembro',
                        ])
                        ->default(now()->format('m'))
                        ->required()
                        ->native(false),
                    
                    \Filament\Forms\Components\Select::make('ano')
                        ->label('Ano')
                        ->options(function () {
                            $anos = [];
                            $anoAtual = now()->year;
                            for ($i = $anoAtual - 5; $i <= $anoAtual + 1; $i++) {
                                $anos[$i] = $i;
                            }
                            return $anos;
                        })
                        ->default(now()->year)
                        ->required()
                        ->native(false),
                ])
                ->action(function (array $data) {
                    return redirect()->route('financeiro.relatorio.mensal.pdf', $data);
                }),

            Actions\Action::make('relatorio_anual')
                ->label('Relatório Anual')
                ->icon('heroicon-o-calendar')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\Select::make('ano')
                        ->label('Ano')
                        ->options(function () {
                            $anos = [];
                            $anoAtual = now()->year;
                            for ($i = $anoAtual - 5; $i <= $anoAtual + 1; $i++) {
                                $anos[$i] = $i;
                            }
                            return $anos;
                        })
                        ->default(now()->year)
                        ->required()
                        ->native(false),
                ])
                ->action(function (array $data) {
                    return redirect()->route('financeiro.relatorio.anual.pdf', $data);
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            MovimentacaoFinanceiraResource\Widgets\FinanceiroStatsWidget::class,
            MovimentacaoFinanceiraResource\Widgets\FluxoCaixaMensalWidget::class,
            MovimentacaoFinanceiraResource\Widgets\FluxoCaixaDiarioWidget::class,
        ];
    }

 public function getHeaderWidgetsColumns(): int | array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 2,
        ];
    }


    public function getTabs(): array
    {
        return [
            'todas' => Tab::make('Todas')
                ->badge(fn () => \App\Models\MovimentacaoFinanceira::count()),

            'entradas' => Tab::make('Entradas')
                ->icon('heroicon-o-arrow-down-circle')
                ->badge(fn () => \App\Models\MovimentacaoFinanceira::where('tipo', 'entrada')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('tipo', 'entrada')),

            'saidas' => Tab::make('Saídas')
                ->icon('heroicon-o-arrow-up-circle')
                ->badge(fn () => \App\Models\MovimentacaoFinanceira::where('tipo', 'saida')->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('tipo', 'saida')),

            'mes_atual' => Tab::make('Este Mês')
                ->icon('heroicon-o-calendar-days')
                ->badge(fn () => \App\Models\MovimentacaoFinanceira::whereMonth('data_movimentacao', now()->month)
                    ->whereYear('data_movimentacao', now()->year)
                    ->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->whereMonth('data_movimentacao', now()->month)
                          ->whereYear('data_movimentacao', now()->year)
                ),

            'ano_atual' => Tab::make('Este Ano')
                ->icon('heroicon-o-calendar')
                ->badge(fn () => \App\Models\MovimentacaoFinanceira::whereYear('data_movimentacao', now()->year)->count())
                ->badgeColor('primary')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->whereYear('data_movimentacao', now()->year)
                ),

            'pendentes' => Tab::make('Pendentes')
                ->icon('heroicon-o-clock')
                ->badge(fn () => \App\Models\MovimentacaoFinanceira::where('status', 'pendente')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pendente')),
        ];
    }
}