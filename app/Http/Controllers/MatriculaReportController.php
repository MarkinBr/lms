<?php

namespace App\Http\Controllers;

use App\Models\Matricula;
use Barryvdh\DomPDF\Facade\Pdf;

class MatriculaReportController extends Controller
{
    public function gerarDeclaracao($id)
    {
        $matricula = Matricula::with(['user', 'curso', 'turma'])->findOrFail($id);
        
        $pdf = Pdf::loadView('pdf.declaracao-matricula', compact('matricula'));
        
        return $pdf->stream("declaracao-{$matricula->numero_matricula}.pdf");
    }
}