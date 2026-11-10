<?php

namespace App\Http\Controllers;

use App\Models\Matricula;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class MatriculaReportController extends Controller
{
    /**
     * Issue #49: declaração, histórico e comprovante passam pela
     * MatriculaPolicy@view (aluno vê a própria; professor vê turmas dele;
     * secretaria/admin veem tudo). Usuário inativo é negado pela Policy.
     */
    public function gerarDeclaracao($id)
    {
        $matricula = Matricula::findOrFail($id);

        Gate::authorize('view', $matricula);

        // Só carrega dados sensíveis depois de autorizado.
        $matricula->loadMissing(['user', 'curso', 'turma']);

        // A view institucional exige estes dados para renderizar o QR Code.
        // Matrículas legadas não possuem código próprio; o número da matrícula
        // funciona como identificador público do documento gerado.
        $codigoValidacao = (string) $matricula->numero_matricula;
        $qrCode = base64_encode(
            QrCode::format('svg')
                ->size(300)
                ->margin(1)
                ->generate(url('/validar').'/'.$codigoValidacao),
        );

        $pdf = Pdf::loadView('pdf.declaracao-matricula', compact('matricula', 'qrCode', 'codigoValidacao'));

        return $pdf->stream("declaracao-{$matricula->numero_matricula}.pdf");
    }

    /**
     * Stub autorizado: a rota já existe e o portal pode referenciá-la,
     * mas a geração do histórico em PDF ainda não foi implementada.
     * Retorna 501 em vez de BadMethodCallException, mantendo a autorização.
     */
    public function gerarHistorico($id): Response
    {
        $matricula = Matricula::findOrFail($id);

        Gate::authorize('view', $matricula);

        return response()->noContent(501);
    }

    /**
     * Stub autorizado: idem gerarHistorico.
     */
    public function gerarComprovante($id): Response
    {
        $matricula = Matricula::findOrFail($id);

        Gate::authorize('view', $matricula);

        return response()->noContent(501);
    }
}
