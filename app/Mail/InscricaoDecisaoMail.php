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
 * Notificação institucional da decisão da Secretaria sobre uma inscrição.
 *
 * O mailable recebe somente dados públicos já mapeados pelo serviço. Isso
 * evita que o template tenha acesso acidental a CPF, RG ou endereço.
 */
class InscricaoDecisaoMail extends Mailable
{
    use Queueable, SerializesModels;

    public const TIPO_MATRICULA = 'matricula';

    public const TIPO_REJEICAO = 'rejeicao';

    public readonly string $situacaoLabel;

    public function __construct(
        public readonly string $nomeCandidato,
        public readonly string $curso,
        public readonly string $modalidade,
        public readonly string $protocolo,
        public readonly string $linkAcompanhamento,
        public readonly string $tipo,
        public readonly ?string $numeroMatricula = null,
        public readonly ?string $motivoRejeicao = null,
    ) {
        if (! in_array($tipo, [self::TIPO_MATRICULA, self::TIPO_REJEICAO], true)) {
            throw new InvalidArgumentException("Tipo de decisão inválido: {$tipo}");
        }

        if ($tipo === self::TIPO_REJEICAO && trim((string) $motivoRejeicao) === '') {
            throw new InvalidArgumentException('A rejeição exige uma justificativa.');
        }

        $this->situacaoLabel = $tipo === self::TIPO_MATRICULA
            ? 'Matrícula confirmada'
            : 'Não aprovada';
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
            subject: $this->tipo === self::TIPO_MATRICULA
                ? 'Matrícula confirmada — CETSADP'
                : 'Resultado da sua inscrição — CETSADP',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.inscricao-decisao',
            with: [
                'nomeCandidato' => $this->nomeCandidato,
                'curso' => $this->curso,
                'modalidade' => $this->modalidade,
                'protocolo' => $this->protocolo,
                'linkAcompanhamento' => $this->linkAcompanhamento,
                'tipo' => $this->tipo,
                'numeroMatricula' => $this->numeroMatricula,
                'motivoRejeicao' => $this->motivoRejeicao,
                'situacaoLabel' => $this->situacaoLabel,
            ],
        );
    }
}
