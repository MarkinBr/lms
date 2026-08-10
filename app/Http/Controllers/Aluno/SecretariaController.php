<?php

namespace App\Http\Controllers\Aluno;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Requerimento;

class SecretariaController extends Controller
{
    public function index()
    {
        $aluno = auth()->user();
        
        // Buscar requerimentos do aluno
        $requerimentos = Requerimento::where('aluno_id', $aluno->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        // Estatísticas
        $estatisticas = [
            'total' => $requerimentos->total(),
            'pendentes' => Requerimento::where('aluno_id', $aluno->id)->where('status', 'pendente')->count(),
            'em_analise' => Requerimento::where('aluno_id', $aluno->id)->where('status', 'em_analise')->count(),
            'aprovados' => Requerimento::where('aluno_id', $aluno->id)->where('status', 'aprovado')->count(),
            'negados' => Requerimento::where('aluno_id', $aluno->id)->where('status', 'negado')->count(),
        ];
        
        return view('aluno.secretaria.index', compact('requerimentos', 'estatisticas'));
    }
    
    public function criar()
    {
        $tiposRequerimento = [
            'declaracao_matricula' => 'Declaração de Matrícula',
            'declaracao_frequencia' => 'Declaração de Frequência',
            'declaracao_conclusao' => 'Declaração de Conclusão',
            'segunda_via_certificado' => 'Segunda Via de Certificado',
            'trancamento' => 'Trancamento de Matrícula',
            'cancelamento' => 'Cancelamento de Matrícula',
            'revisao_nota' => 'Revisão de Nota',
            'outro' => 'Outro',
        ];
        
        return view('aluno.secretaria.criar', compact('tiposRequerimento'));
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'tipo' => 'required|string',
            'assunto' => 'required|string|max:255',
            'descricao' => 'required|string',
            'anexo' => 'nullable|file|max:5120', // 5MB
        ]);
        
        $aluno = auth()->user();
        
        $requerimento = new Requerimento();
        $requerimento->aluno_id = $aluno->id;
        $requerimento->tipo = $request->tipo;
        $requerimento->assunto = $request->assunto;
        $requerimento->descricao = $request->descricao;
        $requerimento->status = 'pendente';
        $requerimento->protocolo = $this->gerarProtocolo();
        
        // Upload de anexo
        if ($request->hasFile('anexo')) {
            $path = $request->file('anexo')->store('requerimentos', 'public');
            $requerimento->anexo = $path;
        }
        
        $requerimento->save();
        
        return redirect()->route('aluno.secretaria')
            ->with('success', 'Requerimento enviado com sucesso! Protocolo: ' . $requerimento->protocolo);
    }
    
    public function show($id)
    {
        $aluno = auth()->user();
        
        $requerimento = Requerimento::where('id', $id)
            ->where('aluno_id', $aluno->id)
            ->firstOrFail();
        
        return view('aluno.secretaria.show', compact('requerimento'));
    }
    
    public function cancelar($id)
    {
        $aluno = auth()->user();
        
        $requerimento = Requerimento::where('id', $id)
            ->where('aluno_id', $aluno->id)
            ->where('status', 'pendente')
            ->firstOrFail();
        
        $requerimento->status = 'cancelado';
        $requerimento->save();
        
        return redirect()->back()->with('success', 'Requerimento cancelado com sucesso!');
    }
    
    private function gerarProtocolo()
    {
        return date('Y') . str_pad(Requerimento::count() + 1, 6, '0', STR_PAD_LEFT);
    }
}
