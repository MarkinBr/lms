<?php

namespace App\Filament\Secretario\Resources\MatriculaSecretariaResource\Pages;

use App\Filament\Secretario\Resources\MatriculaSecretariaResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewMatriculaSecretaria extends ViewRecord
{
    protected static string $resource = MatriculaSecretariaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Editar')
                ->icon('heroicon-o-pencil')
                ->color('warning'),
            
            Actions\Action::make('ver_aluno')
                ->label('Ver Aluno')
                ->icon('heroicon-o-user')
                ->color('info')
                ->url(fn () => route('filament.secretario.resources.aluno-secretarias.edit', [
                    'record' => $this->record->user_id
                ]))
                ->visible(fn () => $this->record->user_id),

            Actions\Action::make('ver_mensalidades')
                ->label('Ver Mensalidades')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->url(fn () => route('filament.secretario.resources.mensalidade-secretarias.index', [
                    'tableFilters' => [
                        'matricula_id' => ['values' => [$this->record->id]]
                    ]
                ])),

            Actions\Action::make('gerar_declaracao')
                ->label('Gerar Declaração')
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->action(function () {
                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.declaracao-matricula', [
                        'matricula' => $this->record
                    ]);
                    
                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, "declaracao-matricula-{$this->record->numero_matricula}.pdf");
                }),

            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informações da Matrícula')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        Infolists\Components\TextEntry::make('numero_matricula')
                            ->label('Número da Matrícula')
                            ->size('lg')
                            ->weight('bold')
                            ->copyable()
                            ->icon('heroicon-o-identification'),
                        
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Aluno')
                            ->url(fn () => route('filament.secretario.resources.aluno-secretarias.edit', [
                                'record' => $this->record->user_id
                            ]))
                            ->icon('heroicon-o-user'),
                        
                        Infolists\Components\TextEntry::make('curso.titulo')
                            ->label('Curso')
                            ->icon('heroicon-o-book-open'),
                        
                        Infolists\Components\TextEntry::make('turma.nome')
                            ->label('Turma')
                            ->icon('heroicon-o-user-group'),
                        
                        Infolists\Components\TextEntry::make('data_matricula')
                            ->label('Data da Matrícula')
                            ->date('d/m/Y')
                            ->icon('heroicon-o-calendar'),
                        
                        Infolists\Components\TextEntry::make('dia_vencimento')
                            ->label('Dia do Vencimento')
                            ->suffix(' de cada mês')
                            ->icon('heroicon-o-calendar-days'),
                        
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'ativo' => 'success',
                                'trancado' => 'warning',
                                'concluido' => 'info',
                                'cancelado' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn ($state) => match($state) {
                                'ativo' => '✅ Ativo',
                                'trancado' => '⏸️ Trancado',
                                'concluido' => '🎓 Concluído',
                                'cancelado' => '❌ Cancelado',
                                default => $state,
                            }),
                        
                        Infolists\Components\TextEntry::make('observacoes')
                            ->label('Observações')
                            ->placeholder('Sem observações')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                
                Infolists\Components\Section::make('Informações do Aluno')
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        Infolists\Components\TextEntry::make('user.cpf')
                            ->label('CPF')
                            ->icon('heroicon-o-identification'),
                        
                        Infolists\Components\TextEntry::make('user.email')
                            ->label('Email')
                            ->icon('heroicon-o-envelope')
                            ->copyable(),
                        
                        Infolists\Components\TextEntry::make('user.telefone')
                            ->label('Telefone')
                            ->icon('heroicon-o-phone')
                            ->url(fn ($record) => "https://wa.me/55" . preg_replace('/[^0-9]/', '', $record->user->telefone ?? ''))
                            ->openUrlInNewTab(),
                        
                        Infolists\Components\TextEntry::make('user.data_nasc')
                            ->label('Data de Nascimento')
                            ->date('d/m/Y')
                            ->icon('heroicon-o-cake')
                            ->placeholder('Não informado'),
                    ])
                    ->columns(2)
                    ->collapsible(),
                
                Infolists\Components\Section::make('Estatísticas de Mensalidades')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_mensalidades')
                            ->label('Total de Mensalidades')
                            ->state(fn ($record) => $record->mensalidades()->count())
                            ->badge()
                            ->color('info'),
                        
                        Infolists\Components\TextEntry::make('mensalidades_pagas')
                            ->label('Mensalidades Pagas')
                            ->state(fn ($record) => $record->mensalidades()->where('status', 'Pago')->count())
                            ->badge()
                            ->color('success')
                            ->icon('heroicon-o-check-circle'),
                        
                        Infolists\Components\TextEntry::make('mensalidades_pendentes')
                            ->label('Mensalidades Pendentes')
                            ->state(fn ($record) => $record->mensalidades()->where('status', 'Pendente')->count())
                            ->badge()
                            ->color('warning')
                            ->icon('heroicon-o-clock'),
                        
                        Infolists\Components\TextEntry::make('mensalidades_atrasadas')
                            ->label('Mensalidades Atrasadas')
                            ->state(fn ($record) => $record->mensalidades()->where('status', 'Atrasado')->count())
                            ->badge()
                            ->color('danger')
                            ->icon('heroicon-o-exclamation-triangle'),
                        
                        Infolists\Components\TextEntry::make('valor_total_pago')
                            ->label('Valor Total Pago')
                            ->state(fn ($record) => 'R$ ' . number_format(
                                $record->mensalidades()->where('status', 'Pago')->sum('valor'), 
                                2, 
                                ',', 
                                '.'
                            ))
                            ->icon('heroicon-o-currency-dollar')
                            ->color('success'),
                        
                        Infolists\Components\TextEntry::make('valor_total_pendente')
                            ->label('Valor Total Pendente')
                            ->state(fn ($record) => 'R$ ' . number_format(
                                $record->mensalidades()->whereIn('status', ['Pendente', 'Atrasado'])->sum('valor'), 
                                2, 
                                ',', 
                                '.'
                            ))
                            ->icon('heroicon-o-currency-dollar')
                            ->color('warning'),
                    ])
                    ->columns(3),
                
                Infolists\Components\Section::make('Informações do Sistema')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Cadastrado em')
                            ->dateTime('d/m/Y H:i'),
                        
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Última Atualização')
                            ->dateTime('d/m/Y H:i')
                            ->since(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}