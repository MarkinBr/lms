<?php

namespace App\Filament\Secretario\Resources\MatriculaSecretariaResource\Pages;

use App\Filament\Secretario\Resources\MatriculaSecretariaResource;
use App\Models\Matricula;
use App\Models\Mensalidade;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

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
                    'record' => $this->record->user_id,
                ]))
                ->visible(fn () => $this->record->user_id),

            Actions\Action::make('ver_mensalidades')
                ->label('Ver Mensalidades')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->url(fn () => route('filament.secretario.resources.mensalidade-secretarias.index', [
                    'tableFilters' => [
                        'matricula_id' => ['values' => [$this->record->id]],
                    ],
                ])),

            Actions\Action::make('gerar_mensalidades')
                ->label('Gerar Mensalidades')
                ->icon('heroicon-o-document-plus')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Gerar Mensalidades')
                ->modalDescription('Deseja gerar as mensalidades desta matrícula?')
                ->form([
                    DatePicker::make('primeira_parcela')
                        ->label('Vencimento da 1ª Parcela')
                        ->default(now()->addMonth()->startOfMonth()->addDays(9))
                        ->native(false)
                        ->required(),

                    TextInput::make('qtd_parcelas')
                        ->label('Quantidade de Parcelas')
                        ->numeric()
                        ->default(fn () => $this->record->turma?->qtd_parcelas ?? 12)
                        ->required(),

                    TextInput::make('valor_parcela')
                        ->label('Valor por Parcela')
                        ->numeric()
                        ->prefix('R$')
                        ->default(fn () => $this->record->turma?->valor_total_curso / ($this->record->turma?->qtd_parcelas ?? 1))
                        ->required(),
                ])
                ->action(function (array $data) {
                    $dataVencimento = Carbon::parse($data['primeira_parcela']);

                    for ($i = 1; $i <= $data['qtd_parcelas']; $i++) {
                        Mensalidade::create([
                            'matricula_id' => $this->record->id,
                            'valor' => $data['valor_parcela'],
                            'data_vencimento' => $dataVencimento->copy()->addMonths($i - 1),
                            'status' => 'Pendente',
                            'observacao' => 'Parcela '.$i.'/'.$data['qtd_parcelas'],
                        ]);
                    }

                    Notification::make()
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
                        'matricula' => $this->record,
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
                ->visible(fn () => in_array(mb_strtolower((string) $this->record->status), ['ativo', 'ativa'], true))
                ->action(function () {
                    $this->record->update(['status' => Matricula::STATUS_TRANCADO]);

                    Notification::make()
                        ->title('Matrícula trancada!')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('reativar')
                ->label('Reativar Matrícula')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => in_array(mb_strtolower((string) $this->record->status), ['trancado', 'trancada'], true))
                ->action(function () {
                    $this->record->update(['status' => Matricula::STATUS_ATIVO]);

                    Notification::make()
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
