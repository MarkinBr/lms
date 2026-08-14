<?php

namespace App\Services;

use App\Mail\ContatoRecebidoMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class NotificarContatoRecebidoService
{
    public function enviar(array $dados): bool
    {
        try {
            Mail::to((string) (config('mail.contact.to.address') ?: 'contato@cetsadp.com.br'))
                ->send(new ContatoRecebidoMail(
                    nome: (string) $dados['nome'],
                    email: (string) $dados['email'],
                    telefone: $dados['telefone'] !== null ? (string) $dados['telefone'] : null,
                    assunto: (string) $dados['assunto'],
                    mensagem: (string) $dados['mensagem'],
                ));

            return true;
        } catch (Throwable $exception) {
            Log::error('Falha ao enviar contato recebido pelo site.', [
                'exception' => $exception::class,
            ]);

            return false;
        }
    }
}
