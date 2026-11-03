<?php

namespace App\Jobs;

use App\Models\FinanceiroEnvio;
use App\Services\WhatsAppService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnviarFinanceiroAgendado implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Evita uma segunda entrega automática em caso de timeout do provedor.
    public int $tries = 1;

    public function __construct(public int $envioId) {}

    public function handle(WhatsAppService $whatsApp): void
    {
        if (! config('financeiro.habilitar_envios_automaticos_secretaria', false)) {
            return;
        }

        $envio = DB::transaction(function () {
            $registro = FinanceiroEnvio::query()->lockForUpdate()->find($this->envioId);
            if (! $registro || $registro->status !== 'agendado' || $registro->agendado_para->isFuture()) {
                return null;
            }
            $registro->update(['status' => 'processando']);

            return $registro;
        });
        if (! $envio) {
            return;
        }

        try {
            $m = $envio->mensalidade()->with(['matricula.user', 'matricula.curso'])->firstOrFail();
            if (($envio->modelo === 'recibo' && ! $m->estaPaga()) ||
                ($envio->modelo !== 'recibo' && ! $m->podeGerarPagamento())) {
                $envio->update(['status' => 'ignorado', 'erro' => 'Situação da mensalidade mudou.']);

                return;
            }

            $user = $m->matricula?->user;
            $nome = (string) ($user?->name ?? 'Estudante');
            $link = match ($envio->modelo) {
                'recibo' => route('recibo.publico.form', $m->codigo_validacao),
                'boleto' => (string) $m->link_boleto,
                'pix' => (string) $m->link_pagamento,
                default => null,
            };
            if (($envio->modelo === 'boleto' && blank($link)) ||
                ($envio->modelo === 'pix' && blank($link) && blank($m->pix_copia_cola))) {
                throw new \RuntimeException('Cobrança sem link ou código do gateway. Não é seguro enviar instruções inventadas.');
            }
            $titulo = match ($envio->modelo) {
                'recibo' => 'Seu pagamento foi confirmado',
                'lembrete' => 'Lembrete de mensalidade',
                'cobranca' => 'Acompanhamento de mensalidade',
                'pix' => 'Seu PIX para pagamento',
                'boleto' => 'Seu boleto bancário',
            };
            $mensagem = match ($envio->modelo) {
                'recibo' => 'Registramos o recebimento da mensalidade indicada abaixo. Seu recibo pode ser conferido no endereço seguro.',
                'lembrete' => 'Informamos o vencimento de sua mensalidade. Caso já tenha realizado o pagamento, desconsidere esta mensagem.',
                'cobranca' => 'Identificamos uma mensalidade em aberto. Caso já tenha efetuado o pagamento, encaminhe a referência para conferência.',
                'pix' => 'O PIX foi gerado pelo Mercado Pago. Utilize o link seguro para realizar o pagamento. A confirmação depende de retorno do provedor.',
                'boleto' => 'O boleto foi gerado pelo Mercado Pago. Utilize o link seguro e observe a data de vencimento e o prazo de compensação.',
            };
            $valor = (float) ($m->valor_pago ?? $m->valor);

            if ($envio->canal === 'email') {
                $dados = [
                    'assunto' => $titulo.' | CETSADP',
                    'titulo' => $titulo,
                    'mensagem' => $mensagem,
                    'aluno' => $nome,
                    'curso' => (string) ($m->matricula?->curso?->titulo ?? ''),
                    'matricula' => (string) ($m->matricula?->numero_matricula ?? ''),
                    'vencimento' => $m->data_vencimento?->format('d/m/Y') ?? '',
                    'valor' => $valor,
                    'link' => $link,
                    'pix_copia_cola' => $envio->modelo === 'pix' ? (string) $m->pix_copia_cola : null,
                    'cta' => $envio->modelo === 'recibo' ? 'Conferir meu recibo' : 'Consultar pagamento',
                ];
                Mail::send('emails.financeiro.aviso', $dados, function ($email) use ($envio, $titulo, $m) {
                    $email->to($envio->destinatario)->subject($titulo.' | CETSADP');
                    if ($envio->modelo === 'recibo') {
                        $pdf = Pdf::loadView('pdf.financeiro-recibo-central', ['mensalidade' => $m])->output();
                        $email->attachData($pdf, 'recibo-cetsadp-'.$m->id.'.pdf', [
                            'mime' => 'application/pdf',
                        ]);
                    }
                });
            } else {
                $texto = "CETSADP — Secretaria Acadêmica\n\nOlá, {$nome}!\n{$mensagem}\n"
                    ."Mensalidade: {$m->data_vencimento?->format('m/Y')} · Valor: R$ "
                    .number_format($valor, 2, ',', '.')."\n";
                if ($link) {
                    $texto .= "\nAcesse: {$link}\n";
                }
                $texto .= "\nEm caso de dúvida, fale com a Secretaria pelos canais oficiais.";
                if (! $whatsApp->enviarMensagem($envio->destinatario, $texto)) {
                    throw new \RuntimeException('Provedor de WhatsApp não confirmou aceitação da mensagem.');
                }
            }

            $envio->update(['status' => 'enviado', 'enviado_em' => now(), 'erro' => null]);
        } catch (\Throwable $erro) {
            Log::warning('Falha no envio financeiro', [
                'envio_id' => $envio->id,
                'classe' => get_class($erro),
            ]);
            $envio->update(['status' => 'erro', 'erro' => 'Falha no provedor; consulte os registros técnicos.']);
        }
    }
}
