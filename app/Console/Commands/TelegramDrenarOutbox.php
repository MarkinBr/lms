<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramOutbox;
use Illuminate\Console\Command;

class TelegramDrenarOutbox extends Command
{
    protected $signature = 'telegram:outbox-drenar {--limite=20 : Máximo de mensagens para tentar}';

    protected $description = 'Tenta entregar mensagens Telegram pendentes de forma segura, apenas no processo com credenciais';

    public function handle(TelegramOutbox $outbox): int
    {
        if (! filled(config('services.telegram.bot_token'))) {
            $this->error('Credenciais Telegram indisponíveis neste processo.');

            return self::FAILURE;
        }

        $limite = filter_var($this->option('limite'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 100],
        ]);
        if ($limite === false) {
            $this->error('O limite deve estar entre 1 e 100.');

            return self::FAILURE;
        }

        $outbox->drenarPendentes($limite);
        $this->info('Tentativa de entrega concluída. Não implica confirmação de recebimento pelo destinatário.');

        return self::SUCCESS;
    }
}
