<?php

namespace App\Http\Controllers\Aluno;

use App\Http\Controllers\Controller;
use App\Models\Matricula;

class NotasController extends Controller
{
    public function index()
    {
        $aluno = auth()->user();

        // Buscar matrículas com notas
        $matriculas = $aluno->matriculas()
            ->with(['curso', 'turma.curso', 'notas' => function ($query) {
                $query->orderByDesc('created_at');
            }])
            ->whereRaw("LOWER(COALESCE(status, '')) IN ('ativo', 'ativa', 'cursando')")
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
                if ($nota->media_final !== null) {
                    $todasNotas[] = (float) $nota->media_final;
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
            ->where('user_id', $aluno->id)
            ->with(['curso', 'turma.curso', 'notas' => function ($query) {
                $query->orderByDesc('created_at');
            }])
            ->firstOrFail();

        // Calcular média do curso
        $notasCurso = $matricula->notas->whereNotNull('media_final');
        $mediaCurso = $notasCurso->avg('media_final') ?? 0;

        return view('aluno.notas.curso', compact('matricula', 'mediaCurso'));
    }
}
