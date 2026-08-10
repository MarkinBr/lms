<?php

namespace App\Filament\Secretario\Resources\MatriculaSecretariaResource\Pages;

use App\Filament\Secretario\Resources\MatriculaSecretariaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Barryvdh\DomPDF\Facade\Pdf;

class EditMatriculaSecretaria extends EditRecord
{
    protected static string $resource = MatriculaSecretariaResource::class;

    protected function getHeaderActions(): array
    {
        return [
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

            Actions\Action::make('gerar_mensalidades')
                ->label('Gerar Mensalidades')
                ->icon('heroicon-o-document-plus')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Gerar Mensalidades')
                ->modalDescription('Deseja gerar as mensalidades desta matrícula?')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('primeira_parcela')
                        ->label('Vencimento da 1ª Parcela')
                        ->default(now()->addMonth()->startOfMonth()->addDays(9))
                        ->native(false)
                        ->required(),
                    
                    \Filament\Forms\Components\TextInput::make('qtd_parcelas')
                        ->label('Quantidade de Parcelas')
                        ->numeric()
                        ->default(fn () => $this->record->turma?->qtd_parcelas ?? 12)
                        ->required(),

                    \Filament\Forms\Components\TextInput::make('valor_parcela')
                        ->label('Valor por Parcela')
                        ->numeric()
                        ->prefix('R$')
                        ->default(fn () => $this->record->turma?->valor_total_curso / ($this->record->turma?->qtd_parcelas ?? 1))
                        ->required(),
                ])
                ->action(function (array $data) {
                    $dataVencimento = \Carbon\Carbon::parse($data['primeira_parcela']);
                    
                    for ($i = 1; $i <= $data['qtd_parcelas']; $i++) {
                        \App\Models\Mensalidade::create([
                            'matricula_id' => $this->record->id,
                            'valor' => $data['valor_parcela'],
                            'data_vencimento' => $dataVencimento->copy()->addMonths($i - 1),
                            'status' => 'Pendente',
                            'observacao' => 'Parcela ' . $i . '/' . $data['qtd_parcelas'],
                        ]);
                    }

                    \Filament\Notifications\Notification::make()
                        ->title('Mensalidades geradas!')
                        ->body("{$data['qtd_parcelas']} mensalidades criadas com sucesso.")
                        ->success()
                        ->send();
                })
                ->visible(fn () => $this->record->mensalidades()->count() === 0),

            Actions\Action::make('gerar_declaracao')
                ->label('Gerar Declaração')
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->action(function () {
                    $pdf = Pdf::loadView('.declaracao-matricula', [
                        'matricula' => $this->record
                    ]);
                    
                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, "declaracao-matricula-{$this->record->numero_matricula}.pdf");
                }),

            Actions\Action::make('trancar')
                ->label('Trancar Matrícula')
                ->icon('heroicon-o-pause-circle')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === 'ativo')
                ->action(function () {
                    $this->record->update(['status' => 'trancado']);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Matrícula trancada!')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('reativar')
                ->label('Reativar Matrícula')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === 'trancado')
                ->action(function () {
                    $this->record->update(['status' => 'ativo']);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Matrícula reativada!')
                        ->success()
                        ->send();
                }),

            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}