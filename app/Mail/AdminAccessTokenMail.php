<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminAccessTokenMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $nome,
        public readonly string $frase,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Seu acesso temporário ao painel CETSADP',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-access-token',
        );
    }
}
