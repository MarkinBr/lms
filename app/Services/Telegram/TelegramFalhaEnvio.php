<?php

namespace App\Services\Telegram;

use RuntimeException;

class TelegramFalhaEnvio extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Não foi possível enviar a resposta ao Telegram.');
    }
}
