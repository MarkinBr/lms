<?php

namespace App\Http\Controllers\Aluno;

use App\Http\Controllers\Controller;
use App\Models\Aula;
use App\Models\Progresso;
use App\Models\ComentarioAula;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AulaController extends Controller
{
    /**
     * Exibe a sala de aula
     */
    public function show($id)
    {
        // --- CORREÇÃO FEITA AQUI ---
        // Removido 'materiais' de dentro do with([]).
        // Agora carrega apenas 'modulo.curso'.
        $aula = Aula::with(['modulo.curso'])->findOrFail($id);
        
        $curso = $aula->modulo->curso;
        
        // Verificar matrícula
        $matriculado = \App\Models\Matricula::where('user_id', Auth::id())
            ->where('curso_id', $curso->id)
            ->whereIn('status', ['ativo', 'concluido'])
            ->exists();

        if (!$matriculado) {
            return redirect()->route('aluno.cursos')->with('error', 'Você não está matriculado neste curso.');
        }

        if (!$aula->liberada) {
            abort(403, 'Esta aula ainda não foi liberada.');
        }

        // Carregar ou criar progresso
        $progresso = Progresso::firstOrCreate([
            'user_id' => Auth::id(),
            'aula_id' => $aula->id,
        ], [
            'progresso_porcentagem' => 0,
            'concluida' => false,
            'tempo_estudo_minutos' => 0
        ]);

        // Carregar dados para Sidebar (Módulos e Aulas com status)
        $modulos = $curso->modulos()
            ->with(['aulas' => function($q) {
                $q->orderBy('ordem')
                  ->with(['progresso' => function($p) {
                      $p->where('user_id', Auth::id());
                  }]);
            }])
            ->orderBy('ordem')
            ->get();

        // Calcular estatísticas
        $totalAulas = $curso->aulas()->count();
        $aulasConcluidas = Progresso::where('user_id', Auth::id())
            ->whereHas('aula.modulo', fn($q) => $q->where('curso_id', $curso->id))
            ->where('concluida', true)
            ->count();
        
        $progressoCurso = $totalAulas > 0 ? round(($aulasConcluidas / $totalAulas) * 100) : 0;

        // Navegação (Anterior / Próxima)
        $todasAulas = collect();
        foreach ($modulos as $mod) {
            foreach ($mod->aulas as $a) {
                $todasAulas->push($a);
            }
        }
        
        // Encontra o índice da aula atual
        $indiceAtual = $todasAulas->search(fn($item) => $item->id === $aula->id);
        $aulaAnterior = $indiceAtual > 0 ? $todasAulas[$indiceAtual - 1] : null;
        $proximaAula = $indiceAtual < ($todasAulas->count() - 1) ? $todasAulas[$indiceAtual + 1] : null;

        // Comentários
        $comentarios = ComentarioAula::with('user')
            ->where('aula_id', $aula->id)
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('aluno.sala-aula', compact(
            'aula',
            'curso',
            'progresso',
            'modulos',
            'totalAulas',
            'aulasConcluidas',
            'progressoCurso',
            'proximaAula',
            'aulaAnterior',
            'comentarios'
        ));
    }

    // --- APIs para AJAX (Fetch/Axios) ---

    public function concluir(Request $request, Aula $aula)
    {
        $progresso = Progresso::where('user_id', Auth::id())
            ->where('aula_id', $aula->id)
            ->first();

        if ($progresso) {
            $progresso->update([
                'concluida' => true,
                'progresso_porcentagem' => 100,
                'data_conclusao' => now(),
            ]);

            return response()->json(['success' => true, 'message' => 'Aula concluída!']);
        }
        return response()->json(['success' => false], 400);
    }

    public function atualizarProgresso(Request $request, Aula $aula)
    {
        $request->validate(['progresso' => 'required|integer|min:0|max:100']);

        $progresso = Progresso::where('user_id', Auth::id())
            ->where('aula_id', $aula->id)
            ->first();

        if ($progresso) {
            if (!$progresso->concluida && $request->progresso > $progresso->progresso_porcentagem) {
                $progresso->update(['progresso_porcentagem' => $request->progresso]);
            }
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 400);
    }

    public function registrarTempo(Request $request, Aula $aula)
    {
        $request->validate(['tempo' => 'required|integer|min:0']);

        $progresso = Progresso::where('user_id', Auth::id())
            ->where('aula_id', $aula->id)
            ->first();

        if ($progresso) {
            $progresso->increment('tempo_estudo_minutos', $request->tempo);
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 400);
    }

    public function salvarQuiz(Request $request, Aula $aula)
    {
        $request->validate([
            'score' => 'required|integer|min:0|max:100',
            'answers' => 'required|array',
            'attempts' => 'nullable|array'
        ]);

        $progresso = Progresso::where('user_id', Auth::id())
            ->where('aula_id', $aula->id)
            ->first();

        if ($progresso) {
            $progresso->update([
                'quiz_score' => $request->score,
                'quiz_respostas' => $request->answers,
                'quiz_tentativas' => $request->attempts,
                'quiz_concluido_em' => now()
            ]);
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 400);
    }

    public function salvarNotas(Request $request, Aula $aula)
    {
        $request->validate(['notas' => 'nullable|string|max:10000']);

        $progresso = Progresso::where('user_id', Auth::id())
            ->where('aula_id', $aula->id)
            ->first();

        if ($progresso) {
            $progresso->update(['notas' => $request->notas]);
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 400);
    }

    public function comentar(Request $request, $id)
    {
        $request->validate([
            'comentario' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:comentarios_aula,id',
        ]);

        $comentario = ComentarioAula::create([
            'aula_id' => $id,
            'user_id' => Auth::id(),
            'parent_id' => $request->parent_id,
            'comentario' => $request->comentario,
            'aprovado' => true, 
        ]);

        $comentario->load('user');

        return response()->json([
            'success' => true,
            'comentario' => $comentario,
        ]);
    }
}