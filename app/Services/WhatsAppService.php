<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private string $apiUrl;
    private string $token;
    private string $instanceId;

    public function __construct()
    {
        $this->apiUrl     = (string) config('services.whatsapp.api_url', '');
        $this->token      = (string) config('services.whatsapp.token', '');
        $this->instanceId = (string) config('services.whatsapp.instance_id', '');
    }

    public function enviarMensagem(string $telefone, string $mensagem): bool
    {
        if ($this->apiUrl === '' || $this->token === '') {
            Log::warning('WhatsApp não configurado — mensagem não enviada');
            return false;
        }

        try {
            $telefone = $this->formatarTelefone($telefone);
            $response = Http::timeout(15)->withHeaders([
                'apikey'       => $this->token,
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}/message/sendText/{$this->instanceId}", [
                'number' => $telefone,
                'text'   => $mensagem,
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning('Falha ao enviar WhatsApp', [
                'telefone' => $telefone,
                'status'   => $response->status(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('Exceção WhatsApp', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * BUG-M05: despacha Job em vez de sleep síncrono.
     */
    public function enviarPixQRCode(string $telefone, string $base64Image, string $copiaECola): bool
    {
        \App\Jobs\EnviarPixWhatsAppJob::dispatch($telefone, $base64Image, $copiaECola)
            ->onQueue('whatsapp');

        return true;
    }

    /**
     * BUG-M10: formatador robusto a espaços, parênteses e variações.
     */
    private function formatarTelefone(string $telefone): string
    {
        $tel = preg_replace('/\D+/', '', $telefone);

        if (strlen($tel) === 9 || strlen($tel) === 8) {
            $ddd = (string) config('services.whatsapp.ddd_padrao', '21');
            $tel = $ddd . $tel;
        }

        if (!str_starts_with($tel, '55')) {
            $tel = '55' . $tel;
        }

        if (strlen($tel) < 12 || strlen($tel) > 13) {
            Log::warning('Telefone com formato anômalo', [
                'original'  => $telefone,
                'formatado' => $tel,
            ]);
        }

        return $tel;
    }

    public function montarMensagemLembrete($mensalidade, int $diasParaVencimento): string
    {
        $aluno = $mensalidade->matricula?->user?->name ?? 'Aluno';
        $valor = $mensalidade->valor_formatado ?? ('R$ ' . number_format((float) $mensalidade->valor, 2, ',', '.'));
        $venc  = $mensalidade->data_vencimento->format('d/m/Y');

        if ($diasParaVencimento > 0) {
            return "🔔 *Lembrete de Mensalidade*\n\nOlá, *{$aluno}*!\n\n" .
                   "Sua mensalidade de *{$valor}* vence em *{$diasParaVencimento} dia(s)* ({$venc}).\n\n" .
                   "Evite multa! Pague antes do vencimento. 😊";
        }

        return "⚠️ *Mensalidade Vence Hoje*\n\nOlá, *{$aluno}*!\n\n" .
               "Sua mensalidade de *{$valor}* vence *HOJE* ({$venc}).\n\n" .
               "Pague agora para evitar multas!";
    }

    public function montarMensagemCobranca($mensalidade): string
    {
        $aluno = $mensalidade->matricula?->user?->name ?? 'Aluno';
        $valor = $mensalidade->valor_formatado ?? ('R$ ' . number_format((float) $mensalidade->valor, 2, ',', '.'));
        $valorComMulta = $mensalidade->valor_com_multa_formatado ?? $valor;
        $venc  = $mensalidade->data_vencimento->format('d/m/Y');
        $dias  = (int) ($mensalidade->dias_atraso ?? 0);

        return "🔴 *Mensalidade em Atraso*\n\nOlá, *{$aluno}*!\n\n" .
               "Sua mensalidade está atrasada há *{$dias} dia(s)*.\n\n" .
               "📅 Vencimento: {$venc}\n💵 Valor original: {$valor}\n" .
               "💰 Valor com multa: *{$valorComMulta}*\n\n" .
               "Regularize para evitar problemas com a matrícula.";
    }

    public function montarMensagemConfirmacaoPagamento($mensalidade): string
    {
        $aluno = $mensalidade->matricula?->user?->name ?? 'Aluno';
        $valor = $mensalidade->valor_formatado ?? ('R$ ' . number_format((float) $mensalidade->valor, 2, ',', '.'));
        $data  = optional($mensalidade->data_pagamento)->format('d/m/Y') ?? '—';

        return "✅ *Pagamento Confirmado*\n\nOlá, *{$aluno}*!\n\n" .
               "Confirmamos o recebimento de *{$valor}* em {$data}.\n\n" .
               "Obrigado! 🎉";
    }
}