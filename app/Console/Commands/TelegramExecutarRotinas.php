<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramOutbox;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramExecutarRotinas extends Command
{
    protected $signature = 'telegram:rotinas {--uma-vez : Executa um ciclo sem manter o processo ativo}';

    protected $description = 'Executa exclusivamente alertas/outbox Telegram, sem disparar cobranças ou a agenda geral';

    public function handle(TelegramOutbox $outbox): int
    {
        if (! filled(config('services.telegram.bot_token'))) {
            $this->error('Credenciais Telegram ausentes neste processo.');

            return self::FAILURE;
        }

        do {
            try {
                if ((int) now('America/Sao_Paulo')->format('H') >= 8) {
                    $this->callSilent('telegram:alertas-diarios');
                }
                $outbox->drenarPendentes();
            } catch (Throwable $erro) {
                // URLs de transporte podem conter token: registrar somente a classe.
                Log::error('Falha nas rotinas Telegram.', ['classe' => $erro::class]);
                if ($this->option('uma-vez')) {
                    return self::FAILURE;
                }
            }
            if ($this->option('uma-vez')) {
                return self::SUCCESS;
            }
            sleep(60);
        } while (true);
    }
}
