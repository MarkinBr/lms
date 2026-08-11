<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class InscricaoConfirmacaoMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param Collection<int, Application> $inscricoes
     */
    public function __construct(public readonly Collection $inscricoes)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmação de inscrição — CETSADP',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.inscricao-confirmacao',
        );
    }
}
