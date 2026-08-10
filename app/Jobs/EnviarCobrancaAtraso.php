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

class EnviarCobrancaAtraso implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public int $mensalidadeId,
        public string $tipoEscalonamento  // cobranca_1dia | cobranca_7dias | ...
    ) {}

    public function handle(WhatsAppService $whatsapp, EmailMensalidadeService $email): void
    {
        $m = Mensalidade::with(['matricula.user'])->find($this->mensalidadeId);

        if (!$m || $m->estaPaga() || $m->estaCancelada()) return;
        if (!$m->estaAtrasada()) return;

        // Idempotência: já enviou este tipo HOJE?
        $jaEnviou = MensalidadeCobranca::where('mensalidade_id', $m->id)
            ->where('tipo', $this->tipoEscalonamento)
            ->whereDate('created_at', today())
            ->exists();

        if ($jaEnviou) {
            Log::debug('Cobrança já enviada hoje — skip', [
                'mens' => $m->id, 'tipo' => $this->tipoEscalonamento,
            ]);
            return;
        }

        $user = $m->matricula?->user;
        if (!$user) return;

        // === WhatsApp ===
        if ($user->telefone) {
            $cobrancaWpp = MensalidadeCobranca::create([
                'mensalidade_id' => $m->id,
                'tipo'           => $this->tipoEscalonamento,
                'canal'          => 'whatsapp',
                'status'         => 'pendente',
            ]);

            try {
                $msg = $this->montarMensagem($m, $whatsapp);
                $cobrancaWpp->update(['conteudo' => $msg]);

                $ok = $whatsapp->enviarMensagem($user->telefone, $msg);
                $ok ? $cobrancaWpp->marcarComoEnviado()
                    : $cobrancaWpp->marcarComoErro('Envio WhatsApp falhou');
            } catch (\Throwable $e) {
                $cobrancaWpp->marcarComoErro($e->getMessage());
            }
        }

        // === Email ===
        if ($user->email && filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            $cobrancaEmail = MensalidadeCobranca::create([
                'mensalidade_id' => $m->id,
                'tipo'           => $this->tipoEscalonamento,
                'canal'          => 'email',
                'status'         => 'pendente',
            ]);

            try {
                $email->enviarCobranca($m);
                $cobrancaEmail->marcarComoEnviado();
            } catch (\Throwable $e) {
                $cobrancaEmail->marcarComoErro($e->getMessage());
            }
        }
    }

    /**
     * Monta mensagem com tom escalonado conforme o tempo de atraso.
     */
    private function montarMensagem(Mensalidade $m, WhatsAppService $whatsapp): string
    {
        // Para 1 dia, tom amigável; 30+ dias, tom firme
        $diasAtraso = $m->dias_atraso;

        $base = $whatsapp->montarMensagemCobranca($m);

        $assinatura = match (true) {
            $diasAtraso <= 1  => "\n\n💡 Pode ter sido só um esquecimento! Pague agora pelo portal.",
            $diasAtraso <= 7  => "\n\n📞 Em caso de dificuldade, entre em contato com a secretaria.",
            $diasAtraso <= 15 => "\n\n⚠️ Atenção: a continuidade do curso depende da regularização.",
            default           => "\n\n🚨 Última notificação antes de medidas administrativas. Procure a secretaria URGENTE.",
        };

        return $base . $assinatura;
    }
}