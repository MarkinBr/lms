<?php

namespace App\Http\Controllers\Aluno;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Declaracao;
use App\Models\Matricula;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DocumentosController extends Controller
{
    public function index()
    {
        $aluno = auth()->user();
        
        $declaracoes = Declaracao::whereIn('matricula_id', $aluno->matriculas()->pluck('id'))
            ->with(['matricula.turma.curso']) 
            ->orderBy('created_at', 'desc')
            ->get();

        // Filtra para mostrar apenas a última de cada tipo (opcional)
        $declaracoes = $declaracoes->unique(function ($item) {
            return $item->matricula_id . '-' . $item->tipo;
        });
        
        $matriculasAtivas = $aluno->matriculas()
            ->whereIn('status', ['ativa', 'ativo', 'cursando']) 
            ->with('turma.curso')
            ->get();
        
        return view('aluno.documentos.index', compact('declaracoes', 'matriculasAtivas'));
    }
    
    public function solicitarDeclaracao(Request $request)
    {
        $request->validate([
            'matricula_id' => 'required|exists:matriculas,id',
            'tipo' => 'required|in:matricula,frequencia,conclusao',
            'finalidade' => 'required|string|max:255',
        ]);
        
        $aluno = auth()->user();
        
        $matricula = Matricula::where('id', $request->matricula_id)
            ->where('user_id', $aluno->id)
            ->firstOrFail();

        // Evita duplicidade no mesmo dia
        $existente = Declaracao::where('matricula_id', $matricula->id)
            ->where('tipo', $request->tipo)
            ->whereDate('created_at', now())
            ->first();

        if ($existente) {
            return redirect()->route('aluno.documentos')
                ->with('info', 'Documento já gerado hoje. Visualize ou baixe na lista abaixo.');
        }
        
        Declaracao::create([
            'matricula_id' => $matricula->id,
            'tipo' => $request->tipo,
            'finalidade' => $request->finalidade,
            'codigo_validacao' => $this->gerarCodigoVerificacao(), 
            'data_emissao' => now(),
            'status' => 'emitida',
        ]);
        
        return redirect()->route('aluno.documentos')
            ->with('success', 'Solicitação realizada com sucesso!');
    }
    
    // --- MÉTODOS DE DOWNLOAD (BAIXAR ARQUIVO) ---

    public function download($id)
    {
        return $this->gerarPdfDeclaracao($id, 'download');
    }

    public function historicoEscolar($matriculaId)
    {
        return $this->gerarPdfHistorico($matriculaId, 'download');
    }

    // --- MÉTODOS DE VISUALIZAÇÃO (ABRIR NO NAVEGADOR) ---

    public function visualizar($id)
    {
        return $this->gerarPdfDeclaracao($id, 'stream');
    }

    public function visualizarHistorico($matriculaId)
    {
        return $this->gerarPdfHistorico($matriculaId, 'stream');
    }

    // --- MÉTODOS PRIVADOS AUXILIARES (PARA NÃO REPETIR CÓDIGO) ---

    private function gerarPdfDeclaracao($id, $modo)
    {
        $aluno = auth()->user();
        
        $declaracao = Declaracao::where('id', $id)
            ->whereHas('matricula', function($query) use ($aluno) {
                $query->where('user_id', $aluno->id);
            })
            ->with(['matricula.turma.curso', 'matricula.user'])
            ->firstOrFail();
        
        $matricula = $declaracao->matricula;
        $codigoValidacao = $declaracao->codigo_validacao ?? $declaracao->codigo_verificacao;

        $urlValidacao = route('validacao.validar', ['codigo' => $codigoValidacao]);
        $qrCode = base64_encode(QrCode::format('svg')->size(300)->margin(1)->generate($urlValidacao));

        $pdf = Pdf::loadView('pdf.declaracao-matricula', [
            'matricula' => $matricula,
            'qrCode' => $qrCode,
            'codigoValidacao' => $codigoValidacao,
            'declaracao' => $declaracao 
        ]);
        
        if ($modo == 'stream') {
            return $pdf->stream("declaracao-{$codigoValidacao}.pdf");
        }
        return $pdf->download("declaracao-{$codigoValidacao}.pdf");
    }

    private function gerarPdfHistorico($matriculaId, $modo)
    {
        $aluno = auth()->user();
        
        $matricula = Matricula::where('id', $matriculaId)
            ->where('user_id', $aluno->id)
            ->with(['turma.curso.modulos', 'user']) // Carrega módulos para o histórico
            ->firstOrFail();
        
        $pdf = Pdf::loadView('pdf.historico-escolar', compact('matricula', 'aluno'));
        
        $nomeArquivo = 'historico-' . str_replace(' ', '_', $aluno->name) . '.pdf';

        if ($modo == 'stream') {
            return $pdf->stream($nomeArquivo);
        }
        return $pdf->download($nomeArquivo);
    }
    
    private function gerarCodigoVerificacao()
    {
        return strtoupper(substr(md5(uniqid(rand(), true)), 0, 12));
    }
}