<?php

namespace App\Filament\Secretario\Resources\AlunoSecretariaResource\Pages;

use App\Filament\Secretario\Resources\AlunoSecretariaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAlunoSecretaria extends EditRecord
{
    protected static string $resource = AlunoSecretariaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('nova_matricula')
                ->label('Nova Matrícula')
                ->icon('heroicon-o-document-plus')
                ->color('success')
                ->url(fn () => route('filament.secretario.resources.matricula-secretarias.create', [
                    'user_id' => $this->record->id
                ])),

            Actions\Action::make('ver_matriculas')
                ->label('Ver Matrículas')
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->url(fn () => route('filament.secretario.resources.matricula-secretarias.index', [
                    'tableFilters' => [
                        'user_id' => ['values' => [$this->record->id]]
                    ]
                ])),

            Actions\Action::make('ver_mensalidades')
                ->label('Ver Mensalidades')
                ->icon('heroicon-o-banknotes')
                ->color('warning')
                ->url(fn () => route('filament.secretario.resources.mensalidade-secretarias.index', [
                    'tableFilters' => [
                        'aluno' => $this->record->id
                    ]
                ])),

            Actions\Action::make('whatsapp')
                ->label('WhatsApp')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('success')
                ->url(fn () => 
                    "https://api.whatsapp.com/send?phone=55" . 
                    preg_replace('/[^0-9]/', '', $this->record->telefone ?? '')
                )
                ->openUrlInNewTab()
                ->visible(fn () => filled($this->record->telefone)),

            Actions\Action::make('resetar_senha')
                ->label('Resetar Senha')
                ->icon('heroicon-o-key')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Resetar senha do aluno')
                ->modalDescription('A senha será alterada para: aluno@123')
                ->action(function () {
                    $this->record->update(['password' => bcrypt('aluno@123')]);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Senha resetada!')
                        ->body('Nova senha: aluno@123')
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