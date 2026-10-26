<?php

namespace App\Jobs;

use App\Models\Application;
use App\Models\Matricula;
use App\Services\NotificarDecisaoInscricaoService;
use App\Services\Telegram\TransporteEmailTelegram;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;

class NotificarDecisaoTelegram implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public function __construct(
        public string $inscricaoId,
        public string $statusEsperado,
        public string $emailHash,
    ) {}

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(NotificarDecisaoInscricaoService $notificador): void
    {
        $inscricao = Application::find($this->inscricaoId);
        if (! $inscricao || $inscricao->status !== $this->statusEsperado
            || ! hash_equals($this->emailHash, hash('sha256', mb_strtolower(trim($inscricao->email))))) {
            return;
        }
        if (! app(TransporteEmailTelegram::class)->disponivel()) {
            throw new RuntimeException('Configure transporte de e-mail real para entregar a decisão acadêmica.');
        }

        if ($this->statusEsperado === Application::STATUS_MATRICULADO) {
            $matricula = Matricula::where('application_id', $inscricao->id)->first();
            if (! $matricula) {
                return;
            }
            $enviado = $notificador->notificarMatricula($inscricao, $matricula->numero_matricula);
        } else {
            $enviado = $notificador->notificarRejeicao($inscricao, (string) $inscricao->observacoes_secretaria);
        }
        if (! $enviado) {
            throw new RuntimeException('Notificação da decisão indisponível; será tentada novamente.');
        }
    }
}
