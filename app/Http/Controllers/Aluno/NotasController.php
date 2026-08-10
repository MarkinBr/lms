<?php

namespace App\Http\Controllers\Aluno;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Nota;
use App\Models\Matricula;

class NotasController extends Controller
{
    public function index()
    {
        $aluno = auth()->user();
        
        // Buscar matrículas com notas
        $matriculas = $aluno->matriculas()
            ->with(['turma.curso', 'notas' => function($query) {
                $query->orderBy('data_avaliacao', 'desc');
            }])
            ->where('status', 'ativa')
            ->get();
        
        // Calcular estatísticas
        $estatisticas = [
            'media_geral' => 0,
            'total_avaliacoes' => 0,
            'aproveitamento' => 0,
            'melhor_nota' => 0,
        ];
        
        $todasNotas = [];
        foreach ($matriculas as $matricula) {
            foreach ($matricula->notas as $nota) {
                if ($nota->nota !== null) {
                    $todasNotas[] = $nota->nota;
                }
            }
        }
        
        if (count($todasNotas) > 0) {
            $estatisticas['media_geral'] = round(array_sum($todasNotas) / count($todasNotas), 2);
            $estatisticas['total_avaliacoes'] = count($todasNotas);
            $estatisticas['melhor_nota'] = max($todasNotas);
            $estatisticas['aproveitamento'] = round(($estatisticas['media_geral'] / 10) * 100, 1);
        }
        
        return view('aluno.notas.index', compact('matriculas', 'estatisticas'));
    }
    
    public function curso($matriculaId)
    {
        $aluno = auth()->user();
        
        $matricula = Matricula::where('id', $matriculaId)
            ->where('aluno_id', $aluno->id)
            ->with(['turma.curso', 'notas' => function($query) {
                $query->orderBy('data_avaliacao', 'desc');
            }])
            ->firstOrFail();
        
        // Calcular média do curso
        $notasCurso = $matricula->notas->where('nota', '!=', null);
        $mediaCurso = $notasCurso->avg('nota');
        
        return view('aluno.notas.curso', compact('matricula', 'mediaCurso'));
    }
}
