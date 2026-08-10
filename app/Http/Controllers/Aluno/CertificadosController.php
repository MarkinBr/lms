<?php

namespace App\Http\Controllers\Aluno;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Certificado;
use App\Models\Matricula;
use Barryvdh\DomPDF\Facade\Pdf;

class CertificadosController extends Controller
{
    public function index()
    {
        $aluno = auth()->user();
        
        // Buscar certificados emitidos
        $certificados = Certificado::where('aluno_id', $aluno->id)
            ->with('curso')
            ->orderBy('data_emissao', 'desc')
            ->get();
        
        // Buscar cursos concluídos sem certificado
        $cursosConcluidosSemCertificado = $aluno->matriculas()
            ->where('status', 'concluida')
            ->whereDoesntHave('certificado')
            ->with('turma.curso')
            ->get();
        
        return view('aluno.certificados.index', compact('certificados', 'cursosConcluidosSemCertificado'));
    }
    
    public function download($id)
    {
        $aluno = auth()->user();
        
        $certificado = Certificado::where('id', $id)
            ->where('aluno_id', $aluno->id)
            ->with('curso')
            ->firstOrFail();
        
        // Gerar PDF do certificado
        $pdf = Pdf::loadView('pdf.certificado', compact('certificado'));
        $pdf->setPaper('A4', 'landscape');
        
        return $pdf->download('certificado-' . $certificado->codigo_verificacao . '.pdf');
    }
    
    public function solicitar(Request $request, $matriculaId)
    {
        $aluno = auth()->user();
        
        $matricula = Matricula::where('id', $matriculaId)
            ->where('aluno_id', $aluno->id)
            ->where('status', 'concluida')
            ->with('turma.curso')
            ->firstOrFail();
        
        // Verificar se já existe certificado
        if ($matricula->certificado) {
            return redirect()->back()->with('error', 'Certificado já emitido para este curso.');
        }
        
        // Criar certificado
        $certificado = Certificado::create([
            'aluno_id' => $aluno->id,
            'curso_id' => $matricula->turma->curso_id,
            'matricula_id' => $matricula->id,
            'codigo_verificacao' => $this->gerarCodigoVerificacao(),
            'data_emissao' => now(),
            'carga_horaria' => $matricula->turma->curso->carga_horaria,
        ]);
        
        return redirect()->route('aluno.certificados')
            ->with('success', 'Certificado emitido com sucesso!');
    }
    
    private function gerarCodigoVerificacao()
    {
        return strtoupper(substr(md5(uniqid(rand(), true)), 0, 16));
    }
}
