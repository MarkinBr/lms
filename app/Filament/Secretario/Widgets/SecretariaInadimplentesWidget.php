<?php

namespace App\Filament\Secretario\Widgets;

use App\Models\Matricula;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class SecretariaInadimplentesWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = '⚠️ Alunos com Mensalidades Atrasadas';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Matricula::query()
                    ->whereHas('mensalidades', fn ($q) => 
                        $q->where('status', 'Atrasado')
                    )
                    ->with(['user', 'curso', 'mensalidades' => fn($q) => 
                        $q->where('status', 'Atrasado')->orderBy('data_vencimento')
                    ])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Aluno')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->user?->telefone ?? 'Sem telefone')
                    ->icon('heroicon-m-user'),

                Tables\Columns\TextColumn::make('curso.titulo')
                    ->label('Curso')
                    ->searchable()
                    ->limit(30)
                    ->icon('heroicon-m-academic-cap'),

                Tables\Columns\TextColumn::make('qtd_atrasadas')
                    ->label('Qtd. Atrasadas')
                    ->badge()
                    ->color('danger')
                    ->state(function ($record) {
                        return $record->mensalidades->count();
                    }),

                Tables\Columns\TextColumn::make('valor_total')
                    ->label('Valor Total')
                    ->money('BRL')
                    ->color('danger')
                    ->weight('bold')
                    ->state(function ($record) {
                        return $record->mensalidades->sum('valor');
                    }),

                Tables\Columns\TextColumn::make('mais_antiga')
                    ->label('Mais Antiga')
                    ->date('d/m/Y')
                    ->state(function ($record) {
                        return $record->mensalidades->first()?->data_vencimento;
                    })
                    ->icon('heroicon-m-calendar'),

                Tables\Columns\TextColumn::make('dias_atraso')
                    ->label('Dias de Atraso')
                    ->badge()
                    ->color(fn ($record) => 
                        $record->mensalidades->first()?->data_vencimento?->diffInDays(now()) > 30 
                            ? 'danger' 
                            : 'warning'
                    )
                    ->state(function ($record) {
                        $maisAntiga = $record->mensalidades->first();
                        if (!$maisAntiga || !$maisAntiga->data_vencimento) {
                            return '-';
                        }
                        return (int) $maisAntiga->data_vencimento->diffInDays(now()) . ' dias';
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->url(fn ($record) => 
                        "https://api.whatsapp.com/send?phone=55" . 
                        preg_replace('/[^0-9]/', '', $record->user->telefone ?? '') .
                        "&text=Olá {$record->user->name}, temos mensalidades em atraso. Podemos conversar?"
                    )
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => filled($record->user?->telefone)),

                Tables\Actions\Action::make('ver_mensalidades')
                    ->label('Ver Mensalidades')
                    ->icon('heroicon-o-banknotes')
                    ->color('primary')
                    ->url(fn ($record) => route('filament.secretario.resources.mensalidade-secretarias.index', [
                        'tableFilters' => [
                            'matricula_id' => ['values' => [$record->id]]
                        ]
                    ])),
            ])
            ->paginated(false)
            ->emptyStateHeading('🎉 Nenhum aluno inadimplente')
            ->emptyStateDescription('Parabéns! Todas as mensalidades estão em dia.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}