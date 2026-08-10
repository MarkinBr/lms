<?php

namespace App\Mail;

use App\Models\Mensalidade;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MensalidadeCobrancaMail extends Mailable
{
    use Queueable, SerializesModels;

    public Mensalidade $mensalidade;

    public function __construct(Mensalidade $mensalidade)
    {
        $this->mensalidade = $mensalidade;
    }

    public function envelope(): Envelope
    {
        // Garante que não quebra se o aluno ou matricula sumirem
        $nome = $this->mensalidade->matricula?->aluno?->nome ?? 'Aluno';
        
        $assunto = match($this->mensalidade->status) {
            'Pago' => "Recibo Confirmado - {$nome}",
            'Atrasado' => "Pendência Financeira - {$nome}",
            default => "Lembrete de Mensalidade - {$nome}",
        };

        return new Envelope(subject: $assunto);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.cobranca', // Certifique-se que este arquivo existe em resources/views/emails/
        );
    }
}