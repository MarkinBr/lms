<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContatoRecebidoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $nome,
        public readonly string $email,
        public readonly ?string $telefone,
        public readonly string $assunto,
        public readonly string $mensagem,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                (string) (config('mail.contact.from.address') ?: config('mail.from.address')),
                (string) (config('mail.contact.from.name') ?: config('mail.from.name')),
            ),
            replyTo: [new Address($this->email, $this->nome)],
            subject: 'Novo contato pelo site — CETSADP',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.contato-recebido');
    }
}
