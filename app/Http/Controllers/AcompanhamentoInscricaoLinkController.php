<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Illuminate\Http\Response;

class AcompanhamentoInscricaoLinkController extends Controller
{
    /**
     * Exibe somente dados públicos por meio de um link temporário assinado.
     */
    public function show(string $application): Response
    {
        $application = Application::query()->findOrFail($application);
        $application->load(['curso', 'courseModality', 'matricula']);

        return response()
            ->view('site.acompanhamento-por-link', [
                'inscricao' => [
                    'nome' => (string) $application->nome_completo,
                    'curso' => (string) optional($application->curso)->titulo,
                    'modalidade' => (string) optional($application->courseModality)->nome,
                    'protocolo' => (string) $application->protocol,
                    'situacao' => $this->statusLabel((string) $application->status),
                    'matricula' => optional($application->matricula)->numero_matricula,
                ],
            ])
            ->header('Cache-Control', 'no-store, private')
            ->header('Pragma', 'no-cache');
    }

    private function statusLabel(string $status): string
    {
        return [
            Application::STATUS_EM_ANALISE => 'Em análise pela secretaria',
            Application::STATUS_APROVADO => 'Aprovado',
            Application::STATUS_REJEITADO => 'Não aprovado',
            Application::STATUS_MATRICULADO => 'Matriculado',
            Application::STATUS_CANCELADO => 'Cancelado',
        ][$status] ?? 'Em análise pela secretaria';
    }
}
