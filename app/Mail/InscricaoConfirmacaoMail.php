<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

class InscricaoConfirmacaoMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param Collection<int, Application> $inscricoes
     */
    /**
     * @var array<string, string>
     */
    public array $linksAcompanhamento;

    public function __construct(public readonly Collection $inscricoes)
    {
        $this->linksAcompanhamento = $this->inscricoes
            ->mapWithKeys(fn (Application $inscricao) => [
                $inscricao->id => URL::temporarySignedRoute(
                    'site.acompanhamento-link',
                    now()->addDays(30),
                    ['application' => $inscricao],
                ),
            ])
            ->all();
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
