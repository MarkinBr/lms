<?php

namespace App\Http\Controllers\Aluno;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Aviso;
use App\Models\Mensagem;
use App\Models\User;

class ComunicacaoController extends Controller
{
    public function index()
    {
        $aluno = auth()->user();
        
        // Buscar avisos recentes
        $avisos = Aviso::where('data_publicacao', '<=', now())
            ->where(function($query) {
                $query->whereNull('data_expiracao')
                      ->orWhere('data_expiracao', '>=', now());
            })
            ->orderBy('data_publicacao', 'desc')
            ->paginate(10);
        
        // Estatísticas
        $estatisticas = [
            'total_avisos' => $avisos->total(),
            'nao_lidos' => Aviso::where('created_at', '>', $aluno->ultima_visualizacao_avisos ?? now()->subDays(30))->count(),
        ];
        
        return view('aluno.comunicacao.index', compact('avisos', 'estatisticas'));
    }
    
    public function show($id)
    {
        $aviso = Aviso::findOrFail($id);
        
        return view('aluno.comunicacao.show', compact('aviso'));
    }
    
    public function mensagens()
    {
        $aluno = auth()->user();
        
        // Aqui você pode implementar um sistema de mensagens
        // Por enquanto, retornamos uma view simples
        
        return view('aluno.comunicacao.mensagens');
    }
    
    public function marcarComoLido(Request $request)
    {
        $aluno = auth()->user();
        $aluno->ultima_visualizacao_avisos = now();
        $aluno->save();
        
        return response()->json(['success' => true]);
    }
}
