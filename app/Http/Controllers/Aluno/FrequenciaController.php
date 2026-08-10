<?php

namespace App\Http\Controllers\Aluno;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AulaProgresso;
use App\Models\Matricula;

class FrequenciaController extends Controller
{
    public function index()
    {
        $aluno = auth()->user();
        
        // Buscar matrículas ativas
        // Certifique-se de que o Model Matricula tem o relacionamento 'progressos' definido
        $matriculas = $aluno->matriculas()
            ->with(['turma.curso.modulos.aulas'])
            ->where('status', 'ativa')
            ->get();
        
        // Calcular frequência para cada matrícula
        $dadosFrequencia = [];
        
        foreach ($matriculas as $matricula) {
            $totalAulas = 0;
            $aulasAssistidas = 0;
            
            // Carregar progressos desta matrícula específica para evitar N+1 queries
            // Assumindo que você tem uma tabela aula_progresso vinculada ao aluno
            $progressos = AulaProgresso::where('aluno_id', $aluno->id)->get();
            
            if ($matricula->turma && $matricula->turma->curso) {
                foreach ($matricula->turma->curso->modulos as $modulo) {
                    $totalAulas += $modulo->aulas->count();
                    
                    foreach ($modulo->aulas as $aula) {
                        // Verifica se existe progresso concluído para esta aula
                        $concluida = $progressos->where('aula_id', $aula->id)
                                              ->where('concluida', true)
                                              ->isNotEmpty();
                        
                        if ($concluida) {
                            $aulasAssistidas++;
                        }
                    }
                }
            }
            
            $percentualFrequencia = $totalAulas > 0 
                ? round(($aulasAssistidas / $totalAulas) * 100, 1) 
                : 0;
            
            $dadosFrequencia[] = [
                'matricula' => $matricula,
                'total_aulas' => $totalAulas,
                'aulas_assistidas' => $aulasAssistidas,
                'percentual' => $percentualFrequencia,
                'status' => $this->getStatusFrequencia($percentualFrequencia),
            ];
        }
        
        // CORREÇÃO: Converter Array para Collection
        $dadosFrequencia = collect($dadosFrequencia);
        
        return view('aluno.frequencia.index', compact('dadosFrequencia'));
    }
    
    public function curso($matriculaId)
    {
        $aluno = auth()->user();
        
        $matricula = Matricula::where('id', $matriculaId)
            ->where('user_id', $aluno->id) // CORREÇÃO: user_id em vez de aluno_id
            ->with(['turma.curso.modulos.aulas'])
            ->firstOrFail();
        
        // Buscar progressos do aluno
        $progressos = AulaProgresso::where('aluno_id', $aluno->id)->get();
        
        // Detalhar frequência por módulo
        $modulosFrequencia = [];
        
        foreach ($matricula->turma->curso->modulos as $modulo) {
            $totalAulasModulo = $modulo->aulas->count();
            $aulasAssitidasModulo = 0;
            $detalhesAulas = [];
            
            foreach ($modulo->aulas as $aula) {
                $progresso = $progressos->where('aula_id', $aula->id)->first();
                
                $concluida = $progresso && $progresso->concluida;
                if ($concluida) {
                    $aulasAssitidasModulo++;
                }
                
                $detalhesAulas[] = [
                    'aula' => $aula,
                    'concluida' => $concluida,
                    'data_conclusao' => $progresso?->updated_at,
                    'tempo_assistido' => $progresso?->tempo_assistido ?? 0,
                ];
            }
            
            $percentualModulo = $totalAulasModulo > 0 
                ? round(($aulasAssitidasModulo / $totalAulasModulo) * 100, 1) 
                : 0;
            
            $modulosFrequencia[] = [
                'modulo' => $modulo,
                'total_aulas' => $totalAulasModulo,
                'aulas_assistidas' => $aulasAssitidasModulo,
                'percentual' => $percentualModulo,
                'detalhes_aulas' => $detalhesAulas,
            ];
        }
        
        // CORREÇÃO: Converter Array para Collection
        $modulosFrequencia = collect($modulosFrequencia);
        
        return view('aluno.frequencia.curso', compact('matricula', 'modulosFrequencia'));
    }
    
    private function getStatusFrequencia($percentual)
    {
        if ($percentual >= 75) {
            return ['label' => 'Adequada', 'color' => 'green'];
        } elseif ($percentual >= 50) {
            return ['label' => 'Atenção', 'color' => 'yellow'];
        } else {
            return ['label' => 'Insuficiente', 'color' => 'red'];
        }
    }
}