<?php

namespace App\Services;

use App\Mail\InscricaoConfirmacaoMail;
use App\Models\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Única porta de envio da confirmação de inscrição recebida.
 *
 * Qualquer falha de preparação ou transporte é capturada sem desfazer as
 * inscrições já persistidas nem alterar a resposta HTTP de sucesso.
 */
class NotificarInscricaoRecebidaService
{
    /**
     * @param  Collection<int, Application>  $inscricoes
     */
    public function enviar(Collection $inscricoes): bool
    {
        if ($inscricoes->isEmpty()) {
            return false;
        }

        try {
            $inscricoes->each->loadMissing(['curso', 'courseModality']);

            $email = $inscricoes->first()->email;
            $todasDoMesmoEmail = $inscricoes->every(
                fn (Application $inscricao) => $inscricao->email === $email,
            );

            if (! $todasDoMesmoEmail) {
                throw new \DomainException(
                    'Todas as inscrições do lote devem pertencer ao mesmo e-mail da requisição.',
                );
            }

            $dados = $inscricoes
                ->map(fn (Application $inscricao) => [
                    'protocol' => $inscricao->protocol,
                    'curso' => $inscricao->curso?->titulo ?? '',
                    'modalidade' => $inscricao->courseModality?->nome ?? '',
                ])
                ->values()
                ->all();

            Mail::to($email)->send(new InscricaoConfirmacaoMail(
                nomeCandidato: $inscricoes->first()->nome_completo,
                inscricoes: $dados,
            ));

            return true;
        } catch (Throwable $exception) {
            Log::warning('Falha ao enviar confirmação de inscrição por e-mail.', [
                'exception' => $exception::class,
                'quantidade_inscricoes' => $inscricoes->count(),
                'application_ids' => $inscricoes->pluck('id')->all(),
            ]);

            return false;
        }
    }
}
