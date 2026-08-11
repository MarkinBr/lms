<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Illuminate\Http\Response;

class AcompanhamentoInscricaoLinkController extends Controller
{
    /**
     * Exibe somente dados seguros de uma inscrição por link temporário assinado.
     */
    public function show(Application $application): Response
    {
        $application->load(['curso', 'courseModality']);

        return response()
            ->view('site.acompanhamento-por-link', [
                'inscricao' => $application,
                'statusLabel' => $this->statusLabel($application->status),
            ])
            ->header('Cache-Control', 'no-store, private');
    }

    private function statusLabel(string $status): string
    {
        return [
            Application::STATUS_EM_ANALISE => 'Em análise pela secretaria',
            Application::STATUS_APROVADO => 'Aprovado',
            Application::STATUS_REJEITADO => 'Não aprovado',
            Application::STATUS_MATRICULADO => 'Matriculado',
            Application::STATUS_CANCELADO => 'Cancelado',
        ][$status] ?? $status;
    }
}
