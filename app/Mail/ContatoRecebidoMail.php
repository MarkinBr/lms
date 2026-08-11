<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ContatoRecebidoMail extends Mailable
{
    public function __construct(public readonly array $dados)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.contact.from_address', config('mail.from.address')),
                config('mail.contact.from_name', config('mail.from.name')),
            ),
            subject: 'Novo contato: '.$this->dados['assunto'],
            replyTo: [new Address($this->dados['email'], $this->dados['nome'])],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.contato-recebido');
    }
}
