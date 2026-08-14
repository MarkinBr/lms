<?php

namespace App\Services;

use App\Mail\InscricaoDecisaoMail;
use App\Models\Application;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Throwable;

/**
 * Única porta de saída para notificações da decisão da Secretaria.
 *
 * Falha de transporte é registrada sem dados pessoais e nunca relançada:
 * a decisão acadêmica já foi persistida antes desta etapa.
 */
class NotificarDecisaoInscricaoService
{
    public function notificarMatricula(Application $inscricao, ?string $numeroMatricula = null): bool
    {
        try {
            $inscricao->loadMissing(['curso', 'courseModality']);

            $mail = new InscricaoDecisaoMail(
                nomeCandidato: (string) $inscricao->nome_completo,
                curso: (string) optional($inscricao->curso)->titulo,
                modalidade: (string) optional($inscricao->courseModality)->nome,
                protocolo: (string) $inscricao->protocol,
                linkAcompanhamento: $this->linkAcompanhamento($inscricao),
                tipo: InscricaoDecisaoMail::TIPO_MATRICULA,
                numeroMatricula: $numeroMatricula !== '' ? $numeroMatricula : null,
            );

            return $this->enviar($inscricao, $mail, InscricaoDecisaoMail::TIPO_MATRICULA);
        } catch (Throwable $exception) {
            return $this->registrarFalha($inscricao, InscricaoDecisaoMail::TIPO_MATRICULA, $exception);
        }
    }

    public function notificarRejeicao(Application $inscricao, string $motivo): bool
    {
        $motivo = trim($motivo);

        if ($motivo === '') {
            Log::warning('Notificação de rejeição ignorada: motivo vazio.', [
                'application_id' => $inscricao->getKey(),
            ]);

            return false;
        }

        try {
            $inscricao->loadMissing(['curso', 'courseModality']);

            $mail = new InscricaoDecisaoMail(
                nomeCandidato: (string) $inscricao->nome_completo,
                curso: (string) optional($inscricao->curso)->titulo,
                modalidade: (string) optional($inscricao->courseModality)->nome,
                protocolo: (string) $inscricao->protocol,
                linkAcompanhamento: $this->linkAcompanhamento($inscricao),
                tipo: InscricaoDecisaoMail::TIPO_REJEICAO,
                motivoRejeicao: $motivo,
            );

            return $this->enviar($inscricao, $mail, InscricaoDecisaoMail::TIPO_REJEICAO);
        } catch (Throwable $exception) {
            return $this->registrarFalha($inscricao, InscricaoDecisaoMail::TIPO_REJEICAO, $exception);
        }
    }

    protected function enviar(Application $inscricao, InscricaoDecisaoMail $mail, string $tipo): bool
    {
        $email = trim((string) $inscricao->email);

        if ($email === '') {
            Log::warning('Notificação de decisão ignorada: inscrição sem e-mail.', [
                'application_id' => $inscricao->getKey(),
                'tipo' => $tipo,
            ]);

            return false;
        }

        try {
            Mail::to($email)->send($mail);

            return true;
        } catch (Throwable $exception) {
            return $this->registrarFalha($inscricao, $tipo, $exception);

        }
    }

    private function registrarFalha(Application $inscricao, string $tipo, Throwable $exception): bool
    {
        Log::error('Falha ao enviar notificação de decisão da inscrição.', [
            'application_id' => $inscricao->getKey(),
            'tipo' => $tipo,
            'exception' => $exception::class,
        ]);

        return false;
    }

    private function linkAcompanhamento(Application $inscricao): string
    {
        return URL::temporarySignedRoute(
            'site.acompanhamento-link',
            now()->addDays(30),
            ['application' => $inscricao->getKey()],
        );
    }
}
