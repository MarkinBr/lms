<?php

namespace App\Services\Telegram;

class TransporteEmailTelegram
{
    public function disponivel(): bool
    {
        return app()->environment('testing') || $this->mailerEntrega((string) config('mail.default'));
    }

    private function mailerEntrega(string $nome, array $visitados = []): bool
    {
        if ($nome === '' || in_array($nome, $visitados, true)) {
            return false;
        }
        $config = config('mail.mailers.'.$nome, []);
        $transporte = $config['transport'] ?? $nome;
        if (in_array($transporte, ['array', 'log'], true)) {
            return false;
        }
        if (in_array($transporte, ['failover', 'roundrobin'], true)) {
            $mailers = $config['mailers'] ?? [];
            if ($mailers === []) {
                return false;
            }
            foreach ($mailers as $mailer) {
                if (! $this->mailerEntrega($mailer, [...$visitados, $nome])) {
                    return false;
                }
            }
        }

        return true;
    }
}
