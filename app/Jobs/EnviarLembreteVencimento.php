<?php

namespace App\Jobs;

use App\Models\Mensalidade;
use App\Models\MensalidadeCobranca;
use App\Services\EmailMensalidadeService;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EnviarLembreteVencimento implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public int $mensalidadeId) {}

    public function handle(WhatsAppService $whatsapp, EmailMensalidadeService $email): void
    {
        $m = Mensalidade::with(['matricula.user', 'matricula.curso'])->find($this->mensalidadeId);

        if (!$m || $m->estaPaga() || $m->estaCancelada()) {
            return;
        }

        $user = $m->matricula?->user;
        if (!$user) return;

        $diasParaVencer = (int) abs(now()->startOfDay()->diffInDays($m->data_vencimento));

        // === WhatsApp ===
        if ($user->telefone) {
            $cobrancaWpp = MensalidadeCobranca::create([
                'mensalidade_id' => $m->id,
                'tipo'           => 'lembrete_3dias',
                'canal'          => 'whatsapp',
                'status'         => 'pendente',
            ]);

            try {
                $msg = $whatsapp->montarMensagemLembrete($m, $diasParaVencer);
                $cobrancaWpp->update(['conteudo' => $msg]);

                $ok = $whatsapp->enviarMensagem($user->telefone, $msg);

                $ok ? $cobrancaWpp->marcarComoEnviado()
                    : $cobrancaWpp->marcarComoErro('Falha no envio WhatsApp');
            } catch (\Throwable $e) {
                $cobrancaWpp->marcarComoErro($e->getMessage());
                Log::error('Falha lembrete WhatsApp', ['mens' => $m->id, 'error' => $e->getMessage()]);
            }
        }

        // === Email ===
        if ($user->email && filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            $cobrancaEmail = MensalidadeCobranca::create([
                'mensalidade_id' => $m->id,
                'tipo'           => 'lembrete_3dias',
                'canal'          => 'email',
                'status'         => 'pendente',
            ]);

            try {
                $email->enviarLembrete($m);
                $cobrancaEmail->marcarComoEnviado();
            } catch (\Throwable $e) {
                $cobrancaEmail->marcarComoErro($e->getMessage());
                Log::error('Falha lembrete email', ['mens' => $m->id, 'error' => $e->getMessage()]);
            }
        }
    }
}