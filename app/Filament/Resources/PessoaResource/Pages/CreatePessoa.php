<?php

namespace App\Filament\Resources\PessoaResource\Pages;

use App\Filament\Resources\PessoaResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreatePessoa extends CreateRecord
{
    protected static string $resource = PessoaResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Pessoa cadastrada com sucesso!';
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        
        Notification::make()
            ->title('✅ Cadastro Concluído!')
            ->body("O usuário {$record->name} foi cadastrado como " . match($record->nivel) {
                'admin' => '👑 Administrador',
                'secretario' => '📚 Secretário',
                'professor' => '🎓 Professor',
                'aluno' => '🎒 Aluno',
                default => $record->nivel,
            })
            ->success()
            ->duration(5000)
            ->send();
    }
}
