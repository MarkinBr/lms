<?php

namespace App\Filament\Professor\Resources\AulaResource\Pages;

use App\Filament\Professor\Resources\AulaResource;
use App\Filament\Professor\Resources\CursoResource;
use App\Models\Disciplina;
use App\Services\GestaoAcademica\PermissoesDocentes;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

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
                ->url(fn () => CursoResource::getUrl('edit', [
                    'record' => $this->record->modulo->curso_id,
                ])),

            Actions\DeleteAction::make()
                ->label('Excluir Aula'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        abort_unless(app(PermissoesDocentes::class)
            ->podeEditarAula((int) auth()->id(), $this->record), 403);

        $disciplina = Disciplina::query()->find($data['disciplina_id'] ?? null);
        $moduloId = (int) ($data['modulo_id'] ?? $disciplina?->modulo_id ?? 0);

        if (! $disciplina || ! app(PermissoesDocentes::class)
            ->podeCriarAula((int) auth()->id(), $disciplina, $moduloId)) {
            // Quem recebeu uma aula específica pode editar essa aula, mas não transferi-la.
            if (! $disciplina || (int) $disciplina->id !== (int) $this->record->disciplina_id
                || $moduloId !== (int) $this->record->modulo_id) {
                throw ValidationException::withMessages([
                    'disciplina_id' => 'Você não tem permissão para mover a aula de disciplina/módulo.',
                ]);
            }
        }

        $data['modulo_id'] = $moduloId;

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
