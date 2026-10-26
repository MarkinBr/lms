<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramAdministrativo;
use Illuminate\Console\Command;

class TelegramRevogarVinculo extends Command
{
    protected $signature = 'telegram:revogar {usuarioId : ID numérico da conta a desvincular}';

    protected $description = 'Revoga vínculo Telegram mesmo quando a conta já está inativa ou rebaixada';

    public function handle(TelegramAdministrativo $bot): int
    {
        $usuarioId = filter_var($this->argument('usuarioId'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($usuarioId === false) {
            $this->error('Informe um ID positivo de usuário.');

            return self::FAILURE;
        }

        // Apenas para operação via console autorizado; não disponibilizar no webhook.
        $bot->revogarVinculo($usuarioId);
        $this->info('Revogação técnica processada. Se havia vínculo, é necessário novo pareamento.');

        return self::SUCCESS;
    }
}
