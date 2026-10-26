<?php

namespace App\Observers;

use App\Models\User;
use App\Services\Telegram\TelegramAdministrativo;

class TelegramUsuarioObserver
{
    public function updated(User $usuario): void
    {
        if (! $usuario->wasChanged(['ativo', 'nivel'])) {
            return;
        }
        if ($usuario->ativo && $usuario->nivel === 'admin') {
            return;
        }

        // Revoga vínculo, confirmações e respostas já enfileiradas de forma atômica
        // com a alteração do usuário, se o chamador estiver em uma transação.
        app(TelegramAdministrativo::class)->revogarVinculo((int) $usuario->id);
    }

    public function deleting(User $usuario): void
    {
        app(TelegramAdministrativo::class)->revogarVinculo((int) $usuario->id);
    }
}
