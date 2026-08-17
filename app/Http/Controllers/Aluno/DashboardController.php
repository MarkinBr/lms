<?php

namespace App\Http\Controllers\Aluno;

use App\Http\Controllers\Controller;
use App\Models\Matricula;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        
        // --- 1. CURSOS E PROGRESSO ---
        $matriculas = Matricula::with(['curso.modulos.aulas'])
            ->where('user_id', $userId)
            ->where('status', 'ativo')
            ->take(4)
            ->get();
            
        $cursos = $matriculas->map(function ($matricula) use ($userId) {
            $curso = $matricula->curso;
            $totalAulas = $curso->modulos->sum(fn($m) => $m->aulas->count());
            
            $aulasAssistidas = DB::table('aula_progresso')
                ->whereIn('aula_id', $curso->modulos->pluck('aulas')->flatten()->pluck('id'))
                ->where('aluno_id', $userId) 
                ->where('concluida', true)
                ->count();
            
            $curso->progresso = $totalAulas > 0 ? round(($aulasAssistidas / $totalAulas) * 100) : 0;
            $curso->aulas_assistidas = $aulasAssistidas;
            $curso->total_aulas = $totalAulas;
            $curso->instrutor = 'Corpo Docente';
            
            return $curso;
        });

        // --- 2. ESTATÍSTICAS DO TOPO ---
        $cursosAtivos = $matriculas->count();
        $progressoMedio = $cursos->count() > 0 ? round($cursos->avg('progresso')) : 0;
        
        // Contagem de próximas aulas (não concluídas)
        $proximasAulas = DB::table('matriculas')
            ->join('modulos', 'modulos.curso_id', '=', 'matriculas.curso_id')
            ->join('aulas', 'aulas.modulo_id', '=', 'modulos.id')
            ->leftJoin('aula_progresso', function($join) use ($userId) {
                $join->on('aula_progresso.aula_id', '=', 'aulas.id')
                     ->where('aula_progresso.aluno_id', '=', $userId);
            })
            ->where('matriculas.user_id', $userId)
            ->where('matriculas.status', 'ativo')
            ->whereNull('aula_progresso.concluida')
            ->count();

        // --- 3. STATUS FINANCEIRO REAL ---
        // Verifica se existe alguma mensalidade vencida e não paga
        $possuiPendencia = DB::table('mensalidades')
            ->join('matriculas', 'mensalidades.matricula_id', '=', 'matriculas.id')
            ->where('matriculas.user_id', $userId)
            ->where('mensalidades.status', '!=', 'Pago')
            ->where('mensalidades.data_vencimento', '<', now())
            ->exists();

        $statusFinanceiro = $possuiPendencia ? 'Pendente' : 'Em dia';

        // --- 4. AVISOS REAIS ---
        $avisos = DB::table('avisos')
            ->where('ativo', 1)
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        // --- 5. DADOS DA BARRA LATERAL (MEU PROGRESSO) ---
        // Totais globais para a barra lateral
        $totalAulasGeral = $cursos->sum('total_aulas');
        $totalAssistidasGeral = $cursos->sum('aulas_assistidas');
        $percentualGeral = $totalAulasGeral > 0 ? round(($totalAssistidasGeral / $totalAulasGeral) * 100) : 0;

        return view('aluno.dashboard', compact(
            'cursos', 
            'cursosAtivos', 
            'progressoMedio', 
            'proximasAulas',
            'statusFinanceiro',
            'avisos',
            'totalAulasGeral',
            'totalAssistidasGeral',
            'percentualGeral'
        ));
    }
}
