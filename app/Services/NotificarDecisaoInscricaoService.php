<?php

namespace App\Services;

use App\Mail\InscricaoDecisaoMail;
use App\Models\Application;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class NotificarDecisaoInscricaoService
{
    public function notificarMatricula(Application $inscricao, string $numeroMatricula): bool
    {
        return $this->enviar(
            $inscricao,
            new InscricaoDecisaoMail($inscricao->loadMissing(['curso', 'courseModality']), InscricaoDecisaoMail::TIPO_MATRICULA, $numeroMatricula),
            'matricula',
        );
    }

    public function notificarRejeicao(Application $inscricao, string $motivo): bool
    {
        $motivo = trim($motivo);

        if ($motivo === '') {
            return false;
        }

        return $this->enviar(
            $inscricao,
            new InscricaoDecisaoMail($inscricao->loadMissing(['curso', 'courseModality']), InscricaoDecisaoMail::TIPO_REJEICAO, motivoRejeicao: $motivo),
            'rejeicao',
        );
    }

    private function enviar(Application $inscricao, InscricaoDecisaoMail $mail, string $tipo): bool
    {
        try {
            Mail::to($inscricao->email)->send($mail);

            return true;
        } catch (Throwable $exception) {
            Log::error('Falha ao enviar notificação de decisão da inscrição.', [
                'application_id' => $inscricao->id,
                'tipo' => $tipo,
                'erro' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
