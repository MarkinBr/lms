<?php

namespace App\Http\Controllers\Aluno;

use App\Http\Controllers\Controller;
use App\Models\AulaAoVivo;
use App\Models\Curso;
use App\Models\Matricula;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CursosController extends Controller
{
    /**
     * Lista todos os cursos do aluno.
     */
    public function index()
    {
        $aluno = auth()->user();
        $statusValidos = ['ativo', 'ativa', 'concluido'];

        $matriculas = Matricula::with([
            'curso.modulos.aulas' => fn ($query) => $query->orderBy('ordem'),
        ])
            ->where('user_id', $aluno->id)
            ->whereIn(DB::raw('LOWER(status)'), $statusValidos)
            ->orderByDesc('created_at')
            ->paginate(9);

        $matriculas->getCollection()->transform(function ($matricula) use ($aluno) {
            $curso = $matricula->curso;

            if (! $curso) {
                $matricula->total_aulas = 0;
                $matricula->aulas_concluidas = 0;
                $matricula->progresso = 0;
                $matricula->tempo_total_minutos = 0;

                return $matricula;
            }

            $aulas = $curso->modulos
                ->flatMap(fn ($modulo) => $modulo->aulas)
                ->filter()
                ->values();

            $aulaIds = $aulas->pluck('id')->all();
            $totalAulas = count($aulaIds);

            $aulasConcluidas = $totalAulas > 0
                ? DB::table('aula_progresso')
                    ->where('aluno_id', $aluno->id)
                    ->whereIn('aula_id', $aulaIds)
                    ->where('concluida', true)
                    ->count()
                : 0;

            $tempoTotalMinutos = $aulas->sum(function ($aula) {
                preg_match('/(\d+)/', (string) ($aula->duracao ?? ''), $matches);

                return (int) ($matches[1] ?? 0);
            });

            $matricula->total_aulas = $totalAulas;
            $matricula->aulas_concluidas = $aulasConcluidas;
            $matricula->progresso = $totalAulas > 0 ? (int) round(($aulasConcluidas / $totalAulas) * 100) : 0;
            $matricula->tempo_total_minutos = $tempoTotalMinutos;

            return $matricula;
        });

        $cursoIds = $matriculas->getCollection()
            ->pluck('curso_id')
            ->filter()
            ->unique()
            ->values();

        $proximasAulasAoVivo = $cursoIds->isNotEmpty()
            ? AulaAoVivo::with(['curso', 'turma'])
                ->whereIn('curso_id', $cursoIds)
                ->where('data_hora', '>=', now())
                ->orderBy('data_hora')
                ->limit(4)
                ->get()
            : collect();

        $resumo = [
            'total_cursos' => $matriculas->total(),
            'ativos' => $matriculas->getCollection()->filter(fn ($m) => strtolower((string) $m->status) !== 'concluido')->count(),
            'concluidos' => $matriculas->getCollection()->filter(fn ($m) => strtolower((string) $m->status) === 'concluido')->count(),
            'progresso_medio' => (int) round($matriculas->getCollection()->avg('progresso') ?? 0),
        ];

        return view('aluno.cursos.index', compact('matriculas', 'proximasAulasAoVivo', 'resumo'));
    }

    /**
     * Exibe os detalhes de um curso específico.
     */
    public function show($id)
    {
        $aluno = auth()->user();

        $matricula = Matricula::where('user_id', $aluno->id)
            ->where('curso_id', $id)
            ->whereIn(DB::raw('LOWER(status)'), ['ativo', 'ativa', 'concluido'])
            ->firstOrFail();

        $curso = Curso::with([
            'modulos' => function ($query) {
                $query->orderBy('ordem')->with([
                    'aulas' => fn ($aulas) => $aulas->orderBy('ordem'),
                ]);
            },
        ])->findOrFail($id);

        $aulas = $curso->modulos->flatMap(fn ($modulo) => $modulo->aulas)->filter()->values();
        $aulaIds = $aulas->pluck('id')->all();

        $progressos = $aulaIds
            ? DB::table('aula_progresso')
                ->where('aluno_id', $aluno->id)
                ->whereIn('aula_id', $aulaIds)
                ->get()
                ->keyBy('aula_id')
            : collect();

        foreach ($curso->modulos as $modulo) {
            foreach ($modulo->aulas as $aula) {
                $aula->concluida_portal = (bool) data_get($progressos->get($aula->id), 'concluida', false);
            }
        }

        $totalModulos = $curso->modulos->count();
        $totalAulas = count($aulaIds);
        $aulasConcluidas = $progressos->where('concluida', true)->count();
        $progresso = $totalAulas > 0 ? (int) round(($aulasConcluidas / $totalAulas) * 100) : 0;
        $modulos = $curso->modulos;
        $materiaisLegados = Schema::hasTable('legado_materiais')
            ? DB::table('legado_materiais')->where('curso_id', $curso->id)->orderBy('nome')->get()
            : collect();

        return view('aluno.cursos.show', compact(
            'curso',
            'matricula',
            'modulos',
            'totalModulos',
            'totalAulas',
            'aulasConcluidas',
            'materiaisLegados',
            'progresso'
        ));
    }
}
