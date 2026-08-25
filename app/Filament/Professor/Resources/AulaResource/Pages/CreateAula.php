<?php

namespace App\Filament\Professor\Resources\AulaResource\Pages;

use App\Filament\Professor\Resources\AulaResource;
use App\Models\Disciplina;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateAula extends CreateRecord
{
    protected static string $resource = AulaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $disciplina = Disciplina::query()
            ->whereKey($data['disciplina_id'] ?? null)
            ->whereHas('modulo.curso', fn ($query) => $query->where('instrutor_id', auth()->id()))
            ->first();

        if (! $disciplina) {
            throw ValidationException::withMessages([
                'disciplina_id' => 'Selecione uma disciplina atribuída aos seus cursos.',
            ]);
        }

        $data['modulo_id'] = $disciplina->modulo_id;

        // Calcular quantidade de questões
        if (isset($data['questoes'])) {
            $data['questoes_quantidade'] = count($data['questoes']);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Aula criada com sucesso!')
            ->body('Continue editando para adicionar mais conteúdo.');
    }
}
