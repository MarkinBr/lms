<?php
// ==========================================
// ListMensalidadesSecretaria.php
// ==========================================
namespace App\Filament\Secretario\Resources\MensalidadeSecretariaResource\Pages;

use App\Filament\Secretario\Resources\MensalidadeSecretariaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Filament\Pages\Concerns\ExposesTableToWidgets;

class ListMensalidadesSecretaria extends ListRecords
{
    use ExposesTableToWidgets;
    
    protected static string $resource = MensalidadeSecretariaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nova Mensalidade')
                ->icon('heroicon-o-plus'),

            Actions\Action::make('relatorio_geral')
                ->label('Relatório Geral')
                ->icon('heroicon-o-document-chart-bar')
                ->color('primary')
                ->url(fn () => route('mensalidades.relatorio.geral.pdf'))
                ->openUrlInNewTab(),

            Actions\Action::make('relatorio_periodo')
                ->label('Relatório por Período')
                ->icon('heroicon-o-calendar')
                ->color('info')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('data_inicio')
                        ->label('Data Início')
                        ->required()
                        ->native(false),
                    \Filament\Forms\Components\DatePicker::make('data_fim')
                        ->label('Data Fim')
                        ->required()
                        ->native(false),
                    \Filament\Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options([
                            'todos' => 'Todos',
                            'Pago' => 'Apenas Pagas',
                            'Pendente' => 'Apenas Pendentes',
                            'Atrasado' => 'Apenas Atrasadas',
                        ])
                        ->default('todos')
                        ->native(false),
                ])
                ->action(function (array $data) {
                    return redirect()->route('mensalidades.relatorio.periodo.pdf', $data);
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            MensalidadeSecretariaResource\Widgets\MensalidadeFinanceiraStats::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'todas' => Tab::make('Todas')
                ->badge(fn () => \App\Models\Mensalidade::count()),

            'pagas' => Tab::make('Pagas')
                ->icon('heroicon-o-check-circle')
                ->badge(fn () => \App\Models\Mensalidade::where('status', 'Pago')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Pago')),

            'pendentes' => Tab::make('Pendentes')
                ->icon('heroicon-o-clock')
                ->badge(fn () => \App\Models\Mensalidade::where('status', 'Pendente')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Pendente')),

            'atrasadas' => Tab::make('Atrasadas')
                ->icon('heroicon-o-exclamation-triangle')
                ->badge(fn () => \App\Models\Mensalidade::where('status', 'Atrasado')->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Atrasado')),

            'vence_hoje' => Tab::make('Vence Hoje')
                ->icon('heroicon-o-calendar-days')
                ->badge(fn () => \App\Models\Mensalidade::whereDate('data_vencimento', today())->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('data_vencimento', today())),

            'canceladas' => Tab::make('Canceladas')
                ->icon('heroicon-o-x-circle')
                ->badge(fn () => \App\Models\Mensalidade::where('status', 'Cancelado')->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Cancelado')),
        ];
    }
}