<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class AtendimentoAcessoMail extends Mailable
{
    public function __construct(private readonly string $mensagem) {}

    public function build(): static
    {
        return $this->subject('Atendimento da Secretaria — CETSADP')
            ->view('emails.atendimento-acesso', ['mensagem' => $this->mensagem]);
    }
}
