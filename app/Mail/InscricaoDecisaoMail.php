<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;

class InscricaoDecisaoMail extends Mailable
{
    use Queueable, SerializesModels;

    public const TIPO_MATRICULA = 'matricula';

    public const TIPO_REJEICAO = 'rejeicao';

    public readonly string $linkAcompanhamento;

    public function __construct(
        public readonly Application $inscricao,
        public readonly string $tipo,
        public readonly ?string $numeroMatricula = null,
        public readonly ?string $motivoRejeicao = null,
    ) {
        if (! in_array($tipo, [self::TIPO_MATRICULA, self::TIPO_REJEICAO], true)) {
            throw new InvalidArgumentException('Tipo de decisão inválido para o e-mail de inscrição.');
        }

        if ($tipo === self::TIPO_REJEICAO && trim((string) $motivoRejeicao) === '') {
            throw new InvalidArgumentException('O e-mail de rejeição exige uma justificativa.');
        }

        $this->linkAcompanhamento = URL::temporarySignedRoute(
            'site.acompanhamento-link',
            now()->addDays(30),
            ['application' => $inscricao],
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->tipo === self::TIPO_MATRICULA
            ? 'Matrícula confirmada — CETSADP'
            : 'Resultado da sua inscrição — CETSADP');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.inscricao-decisao');
    }
}
