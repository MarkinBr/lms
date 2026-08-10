<?php

namespace App\Filament\Professor\Resources\CursoResource\Pages;

use App\Filament\Professor\Resources\CursoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditCurso extends EditRecord
{
    protected static string $resource = CursoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('visualizar_como_aluno')
                ->label('👁️ Visualizar como Aluno')
                ->color('info')
                ->icon('heroicon-o-eye')
                ->url(fn () => route('aluno.curso.show', $this->record->id))
                ->openUrlInNewTab(),

            Actions\Action::make('voltar')
                ->label('Voltar aos Cursos')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(CursoResource::getUrl()),

            Actions\DeleteAction::make()
                ->label('Excluir Curso'),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Curso atualizado!')
            ->body('As alterações foram salvas com sucesso.');
    }
}
