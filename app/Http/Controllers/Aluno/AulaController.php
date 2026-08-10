<?php

namespace App\Http\Controllers\Aluno;

use App\Http\Controllers\Controller;
use App\Models\Aula;
use App\Models\AulaProgresso;
use App\Models\ComentarioAula;
use App\Models\Matricula;
use App\Models\Progresso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AulaController extends Controller
{
    /**
     * Exibe a sala de aula.
     */
    public function show($id)
    {
        $aluno = auth()->user();

        $aula = Aula::with(['modulo.curso'])->findOrFail($id);
        $modulo = $aula->modulo;
        $curso = $modulo?->curso;

        if (!$curso) {
            return redirect()->route('aluno.cursos')
                ->with('error', 'Esta aula não está vinculada a um curso válido.');
        }

        $matriculado = Matricula::where('user_id', $aluno->id)
            ->where('curso_id', $curso->id)
            ->whereIn(DB::raw('LOWER(status)'), ['ativo', 'ativa', 'concluido'])
            ->exists();

        if (!$matriculado) {
            return redirect()->route('aluno.cursos')
                ->with('error', 'Você não está matriculado neste curso.');
        }

        if (!$aula->liberada) {
            return redirect()->route('aluno.curso.show', $curso->id)
                ->with('error', 'Esta aula ainda não foi liberada.');
        }

        // Garante o registro na tabela aula_progresso (usada pelo dashboard / cursos).
        $aulaProgresso = AulaProgresso::firstOrCreate(
            [
                'aluno_id' => $aluno->id,
                'aula_id' => $aula->id,
            ],
            [
                'concluida' => false,
                'tempo_assistido' => 0,
                'ultima_visualizacao' => now(),
            ]
        );

        $aulaProgresso->update(['ultima_visualizacao' => now()]);

        // Mantém também o registro em "progressos" (recursos extras: notas, quiz...).
        $progresso = Progresso::firstOrCreate(
            [
                'user_id' => $aluno->id,
                'aula_id' => $aula->id,
            ],
            [
                'progresso_porcentagem' => $aulaProgresso->concluida ? 100 : 0,
                'concluida' => $aulaProgresso->concluida,
                'tempo_estudo_minutos' => 0,
            ]
        );

        // Sidebar com módulos e aulas + progresso correto.
        $modulos = $curso->modulos()
            ->with([
                'aulas' => fn ($q) => $q->orderBy('ordem'),
            ])
            ->orderBy('ordem')
            ->get();

        $aulasIds = $modulos->flatMap->aulas->pluck('id')->all();

        $progressosAluno = AulaProgresso::where('aluno_id', $aluno->id)
            ->whereIn('aula_id', $aulasIds)
            ->get()
            ->keyBy('aula_id');

        foreach ($modulos as $mod) {
            foreach ($mod->aulas as $a) {
                $registro = $progressosAluno->get($a->id);
                $a->concluida_portal = (bool) ($registro?->concluida ?? false);
            }
        }

        $totalAulas = count($aulasIds);
        $aulasConcluidas = $progressosAluno->where('concluida', true)->count();
        $progressoCurso = $totalAulas > 0
            ? (int) round(($aulasConcluidas / $totalAulas) * 100)
            : 0;

        // Navegação anterior / próxima.
        $todasAulas = $modulos->flatMap->aulas->values();
        $indiceAtual = $todasAulas->search(fn ($item) => (int) $item->id === (int) $aula->id);
        $aulaAnterior = ($indiceAtual !== false && $indiceAtual > 0)
            ? $todasAulas[$indiceAtual - 1]
            : null;
        $proximaAula = ($indiceAtual !== false && $indiceAtual < $todasAulas->count() - 1)
            ? $todasAulas[$indiceAtual + 1]
            : null;

        $comentarios = ComentarioAula::with(['usuario', 'respostas.usuario'])
            ->where('aula_id', $aula->id)
            ->whereNull('parent_id')
            ->where('aprovado', true)
            ->orderByDesc('created_at')
            ->get();

        return view('aluno.sala-aula', compact(
            'aula',
            'curso',
            'modulo',
            'progresso',
            'aulaProgresso',
            'modulos',
            'totalAulas',
            'aulasConcluidas',
            'progressoCurso',
            'proximaAula',
            'aulaAnterior',
            'comentarios'
        ));
    }

    /**
     * Marca a aula como concluída (sincroniza ambas as tabelas).
     */
    public function concluir(Request $request, $id)
    {
        $aluno = auth()->user();
        $aula = Aula::findOrFail($id);

        DB::transaction(function () use ($aluno, $aula) {
            AulaProgresso::updateOrCreate(
                ['aluno_id' => $aluno->id, 'aula_id' => $aula->id],
                [
                    'concluida' => true,
                    'ultima_visualizacao' => now(),
                ]
            );

            Progresso::updateOrCreate(
                ['user_id' => $aluno->id, 'aula_id' => $aula->id],
                [
                    'concluida' => true,
                    'progresso_porcentagem' => 100,
                    'data_conclusao' => now(),
                ]
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Aula concluída!',
        ]);
    }

    /**
     * Atualiza o percentual de progresso (sem regredir).
     */
    public function atualizarProgresso(Request $request, $id)
    {
        $request->validate(['progresso' => 'required|integer|min:0|max:100']);

        $aluno = auth()->user();
        $aula = Aula::findOrFail($id);
        $novoProgresso = (int) $request->input('progresso');

        $progresso = Progresso::firstOrCreate(
            ['user_id' => $aluno->id, 'aula_id' => $aula->id],
            ['progresso_porcentagem' => 0, 'concluida' => false]
        );

        if (!$progresso->concluida && $novoProgresso > $progresso->progresso_porcentagem) {
            $progresso->update(['progresso_porcentagem' => $novoProgresso]);
        }

        if ($novoProgresso >= 100) {
            $this->concluir($request, $id);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Registra tempo de estudo acumulado.
     */
    public function registrarTempo(Request $request, $id)
    {
        $request->validate(['tempo' => 'required|integer|min:0']);

        $aluno = auth()->user();
        $aula = Aula::findOrFail($id);

        $progresso = Progresso::firstOrCreate(
            ['user_id' => $aluno->id, 'aula_id' => $aula->id],
            ['tempo_estudo_minutos' => 0]
        );

        $progresso->increment('tempo_estudo_minutos', (int) $request->input('tempo'));

        AulaProgresso::where('aluno_id', $aluno->id)
            ->where('aula_id', $aula->id)
            ->update(['ultima_visualizacao' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * Salva resultado de quiz.
     */
    public function salvarQuiz(Request $request, $id)
    {
        $request->validate([
            'score' => 'required|integer|min:0|max:100',
            'answers' => 'required|array',
            'attempts' => 'nullable|array',
        ]);

        $aluno = auth()->user();
        $aula = Aula::findOrFail($id);

        $progresso = Progresso::firstOrCreate(
            ['user_id' => $aluno->id, 'aula_id' => $aula->id],
            ['progresso_porcentagem' => 0, 'concluida' => false]
        );

        $progresso->update([
            'quiz_score' => $request->input('score'),
            'quiz_respostas' => $request->input('answers'),
            'quiz_tentativas' => $request->input('attempts', []),
            'quiz_concluido_em' => now(),
        ]);

        $notaMinima = $aula->nota_minima_aprovacao ?: 60;
        if ((int) $request->input('score') >= $notaMinima) {
            $this->concluir($request, $id);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Salva anotações pessoais da aula.
     */
    public function salvarNotas(Request $request, $id)
    {
        $request->validate(['notas' => 'nullable|string|max:10000']);

        $aluno = auth()->user();
        $aula = Aula::findOrFail($id);

        $progresso = Progresso::firstOrCreate(
            ['user_id' => $aluno->id, 'aula_id' => $aula->id],
            []
        );

        $progresso->update(['notas' => $request->input('notas')]);

        return response()->json(['success' => true]);
    }

    /**
     * Cria comentário (com suporte a respostas).
     */
    public function comentar(Request $request, $id)
    {
        $request->validate([
            'comentario' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:comentarios_aula,id',
        ]);

        $aluno = auth()->user();
        $aula = Aula::findOrFail($id);

        $comentario = ComentarioAula::create([
            'aula_id' => $aula->id,
            'user_id' => $aluno->id,
            'parent_id' => $request->input('parent_id'),
            'comentario' => $request->input('comentario'),
            'aprovado' => true,
        ]);

        $comentario->load('usuario');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'comentario' => $comentario,
            ]);
        }

        return back()->with('success', 'Comentário publicado!');
    }
}
