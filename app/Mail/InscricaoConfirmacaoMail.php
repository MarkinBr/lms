<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use InvalidArgumentException;

/**
 * Confirmação institucional de inscrição recebida.
 *
 * O mailable recebe somente dados públicos já mapeados, nunca o modelo
 * Application completo, para não expor campos sensíveis ao template.
 */
class InscricaoConfirmacaoMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array{protocol: string, curso: string, modalidade: string}>  $inscricoes
     */
    public function __construct(
        public readonly string $nomeCandidato,
        public readonly array $inscricoes,
    ) {
        $total = count($inscricoes);

        if ($total < 1 || $total > 3) {
            throw new InvalidArgumentException(
                'A confirmação de inscrição aceita de 1 a 3 inscrições por mensagem.',
            );
        }
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                (string) config('mail.inscricoes.from.address', config('mail.from.address')),
                (string) config('mail.inscricoes.from.name', config('mail.from.name')),
            ),
            replyTo: [
                new Address(
                    (string) config('mail.inscricoes.reply_to.address', config('mail.from.address')),
                    (string) config('mail.inscricoes.reply_to.name', config('mail.from.name')),
                ),
            ],
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
