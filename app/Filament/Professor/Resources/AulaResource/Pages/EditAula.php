<?php

namespace App\Filament\Professor\Resources\AulaResource\Pages;

use App\Filament\Professor\Resources\AulaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditAula extends EditRecord
{
    protected static string $resource = AulaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('preview')
                ->label('👁️ Visualizar como Aluno')
                ->color('info')
                ->icon('heroicon-o-eye')
                ->url(fn () => route('aluno.aula', $this->record->id))
                ->openUrlInNewTab(),

            Actions\Action::make('voltar_modulo')
                ->label('← Voltar ao Módulo')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(fn () => \App\Filament\Professor\Resources\CursoResource::getUrl('edit', [
                    'record' => $this->record->modulo->curso_id
                ])),

            Actions\DeleteAction::make()
                ->label('Excluir Aula'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Calcular quantidade de questões
        if (isset($data['questoes'])) {
            $data['questoes_quantidade'] = count($data['questoes']);
        }

        return $data;
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Aula atualizada!')
            ->body('Todas as alterações foram salvas.');
    }
}
