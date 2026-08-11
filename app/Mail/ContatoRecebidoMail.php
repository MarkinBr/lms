<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;

class ContatoRecebidoMail extends Mailable
{
    public function __construct(public readonly array $dados)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Novo contato: '.$this->dados['assunto'],
            replyTo: [new Address($this->dados['email'], $this->dados['nome'])],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.contato-recebido');
    }
}
