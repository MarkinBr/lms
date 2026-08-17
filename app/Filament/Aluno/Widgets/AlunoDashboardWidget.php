<?php

namespace App\Filament\Aluno\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class AlunoDashboardWidget extends Widget
{
    protected static string $view = 'filament.aluno.widgets.aluno-dashboard-widget';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 1;

    public function getData(): array
    {
        $aluno = Auth::user();
        
        return [
            'aluno' => $aluno,
            'ultimaAula' => $this->getUltimaAulaAssistida(),
            'progresso' => $this->getProgressoGeral(),
            'proximasAulas' => $this->getProximasAulasAoVivo(),
            'mensalidadePendente' => $this->getMensalidadePendente(),
            'avisos' => $this->getAvisosImportantes(),
            'cursosAtivos' => $this->getCursosAtivos(),
        ];
    }

    protected function getUltimaAulaAssistida()
    {
        // Busca a última aula que o aluno assistiu
        return \DB::table('aula_progresso')
            ->join('aulas', 'aulas.id', '=', 'aula_progresso.aula_id')
            ->join('modulos', 'modulos.id', '=', 'aulas.modulo_id')
            ->join('cursos', 'cursos.id', '=', 'modulos.curso_id')
            ->where('aula_progresso.aluno_id', Auth::id())
            ->orderBy('aula_progresso.updated_at', 'desc')
            ->select('aulas.*', 'modulos.titulo as modulo', 'cursos.titulo as curso', 'cursos.capa')
            ->first();
    }

    protected function getProgressoGeral()
    {
        // Calcula o progresso geral do aluno em todos os cursos
        $totalAulas = \DB::table('matriculas')
            ->join('cursos', 'cursos.id', '=', 'matriculas.curso_id')
            ->join('modulos', 'modulos.curso_id', '=', 'cursos.id')
            ->join('aulas', 'aulas.modulo_id', '=', 'modulos.id')
            ->where('matriculas.user_id', Auth::id())
            ->count();

        $aulasAssistidas = \DB::table('aula_progresso')
            ->where('aluno_id', Auth::id())
            ->where('concluida', true)
            ->count();

        return $totalAulas > 0 ? round(($aulasAssistidas / $totalAulas) * 100) : 0;
    }

    protected function getProximasAulasAoVivo()
    {
        // Busca aulas ao vivo agendadas nos próximos 7 dias
        return \DB::table('aulas_ao_vivo')
            ->join('cursos', 'cursos.id', '=', 'aulas_ao_vivo.curso_id')
            ->join('matriculas', 'matriculas.curso_id', '=', 'cursos.id')
            ->where('matriculas.user_id', Auth::id())
            ->where('aulas_ao_vivo.data_hora', '>=', now())
            ->where('aulas_ao_vivo.data_hora', '<=', now()->addDays(7))
            ->orderBy('aulas_ao_vivo.data_hora')
            ->limit(3)
            ->select('aulas_ao_vivo.*', 'cursos.titulo as curso')
            ->get();
    }

    protected function getMensalidadePendente()
    {
        $user = auth()->user();
        // Verifica mensalidades vencendo nos próximos 3 dias
        return \DB::table('mensalidades')
    ->join('matriculas', 'matriculas.id', '=', 'mensalidades.matricula_id')
    ->where('matriculas.user_id', $user->id) // Filtra pelo User ID na matrícula
    ->where('mensalidades.status', '!=', 'Pago') // Ajuste conforme seus status ('Pendente', 'Atrasado')
    ->whereBetween('mensalidades.data_vencimento', [now(), now()->addDays(7)]) // Exemplo de data
    ->orderBy('mensalidades.data_vencimento', 'asc')
    ->select('mensalidades.*')
    ->first();
    }

    protected function getAvisosImportantes()
    {
        // Busca avisos ativos da secretaria
        return \DB::table('avisos')
            ->where('ativo', true)
            ->where(function($query) {
                $query->whereNull('data_expiracao')
                      ->orWhere('data_expiracao', '>=', now());
            })
            ->orderBy('created_at', 'desc')
            ->limit(2)
            ->get();
    }

    protected function getCursosAtivos()
    {
        // Busca cursos em que o aluno está matriculado
        return \DB::table('matriculas')
            ->join('cursos', 'cursos.id', '=', 'matriculas.curso_id')
            ->where('matriculas.user_id', Auth::id())
            ->whereRaw("LOWER(COALESCE(matriculas.status, '')) IN ('ativo', 'ativa', 'cursando')")
            ->select('cursos.*', 'matriculas.data_matricula')
            ->get()
            ->map(function($curso) {
                // Calcula progresso de cada curso
                $totalAulas = \DB::table('modulos')
                    ->join('aulas', 'aulas.modulo_id', '=', 'modulos.id')
                    ->where('modulos.curso_id', $curso->id)
                    ->count();

                $aulasAssistidas = \DB::table('aula_progresso')
                    ->join('aulas', 'aulas.id', '=', 'aula_progresso.aula_id')
                    ->join('modulos', 'modulos.id', '=', 'aulas.modulo_id')
                    ->where('modulos.curso_id', $curso->id)
                    ->where('aula_progresso.aluno_id', Auth::id())
                    ->where('aula_progresso.concluida', true)
                    ->count();

                $curso->progresso = $totalAulas > 0 ? round(($aulasAssistidas / $totalAulas) * 100) : 0;
                $curso->total_aulas = $totalAulas;
                $curso->aulas_assistidas = $aulasAssistidas;

                return $curso;
            });
    }
}
