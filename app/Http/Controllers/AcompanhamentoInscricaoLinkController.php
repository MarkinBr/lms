<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Illuminate\Http\Response;

class AcompanhamentoInscricaoLinkController extends Controller
{
    public function show(Application $application): Response
    {
        $application->load(['curso', 'courseModality']);

        return response()
            ->view('site.acompanhamento-por-link', [
                'inscricao' => $application,
                'statusLabel' => [
                    Application::STATUS_EM_ANALISE => 'Em análise pela secretaria',
                    Application::STATUS_APROVADO => 'Aprovado',
                    Application::STATUS_REJEITADO => 'Não aprovado',
                    Application::STATUS_MATRICULADO => 'Matriculado',
                    Application::STATUS_CANCELADO => 'Cancelado',
                ][$application->status] ?? $application->status,
            ])
            ->header('Cache-Control', 'no-store, private');
    }
}
