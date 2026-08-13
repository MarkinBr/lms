<?php

namespace App\Http\Controllers\Aluno;

use App\Http\Controllers\Controller;
use App\Models\AulaAoVivo;
use App\Models\Aviso;
use App\Models\EventoCalendario;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CalendarioController extends Controller
{
    public function index()
    {
        $dados = $this->montarDadosCalendario();

        return view('aluno.calendario.index', $dados);
    }

    public function proximosEventos()
    {
        $dados = $this->montarDadosCalendario();

        $eventos = collect($dados['eventos'])
            ->filter(function (array $evento) {
                $inicio = $evento['start'] ?? null;

                return $inicio
                    && Carbon::parse($inicio)->between(now()->startOfDay(), now()->addDays(7)->endOfDay());
            })
            ->values();

        return response()->json($eventos);
    }

    private function montarDadosCalendario(): array
    {
        $aluno = auth()->user();
        $statusAtivos = ['ativo', 'ativa'];

        $matriculas = $aluno->matriculas()
            ->with(['curso', 'turma.curso'])
            ->whereIn(DB::raw('LOWER(status)'), $statusAtivos)
            ->get();

        $cursoIds = $matriculas->pluck('curso_id')->filter()->unique()->values();
        $turmaIds = $matriculas->pluck('turma_id')->filter()->unique()->values();

        // Issue #25: encontros (AulaAoVivo) são restritos à(s) turma(s) em que
        // o aluno possui MatriculaTurma ativa (nunca por curso "solto"), para
        // não expor encontros de outra turma do mesmo curso. Consulta
        // AulaAoVivo diretamente — sem duplicar em EventoCalendario.
        $aulasAoVivo = AulaAoVivo::with(['curso', 'turma'])
            ->paraAluno($aluno)
            ->proximas()
            ->get();

        $avisos = Aviso::query()
            ->where('ativo', true)
            ->where(function ($query) {
                $query->whereNull('data_publicacao')
                    ->orWhere('data_publicacao', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('data_expiracao')
                    ->orWhere('data_expiracao', '>=', now()->toDateString());
            })
            ->orderByDesc('data_publicacao')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        $eventosSecretaria = EventoCalendario::with(['curso', 'turma'])
            ->where('ativo', true)
            ->where(function ($query) use ($cursoIds, $turmaIds) {
                $query->where(function ($subQuery) {
                    $subQuery->whereNull('curso_id')->whereNull('turma_id');
                });

                if ($cursoIds->isNotEmpty()) {
                    $query->orWhereIn('curso_id', $cursoIds->all());
                }

                if ($turmaIds->isNotEmpty()) {
                    $query->orWhereIn('turma_id', $turmaIds->all());
                }
            })
            ->orderBy('data_inicio')
            ->get();

        $eventos = $this->mapearEventos($aulasAoVivo, $eventosSecretaria, $avisos);

        return [
            'eventos' => $eventos,
            'aulasAoVivo' => $aulasAoVivo,
            'avisos' => $avisos,
            'matriculas' => $matriculas,
            'eventosSecretaria' => $eventosSecretaria,
        ];
    }

    private function mapearEventos(Collection $aulasAoVivo, Collection $eventosSecretaria, Collection $avisos): array
    {
        $eventos = collect();

        foreach ($aulasAoVivo as $aula) {
            $inicio = Carbon::parse($aula->data_hora);
            $fim = Carbon::parse($aula->data_hora)->addMinutes($aula->duracao ?: 60);
            $ehOnline = $aula->modalidade === AulaAoVivo::MODALIDADE_ONLINE;

            $eventos->push([
                'id' => 'aula-'.$aula->id,
                'title' => $aula->titulo,
                'start' => $inicio->toIso8601String(),
                'end' => $fim->toIso8601String(),
                'type' => 'aula_vivo',
                // Nunca expõe link de encontro que não pertença à matrícula do
                // aluno: a coleção já vem filtrada por paraAluno(); aqui só
                // decidimos exibir link (online) ou local (presencial).
                'url' => $ehOnline ? $aula->link_transmissao : null,
                'color' => '#2563eb',
                'description' => $aula->descricao,
                'turma' => $aula->turma->nome ?? null,
                'curso' => $aula->curso->titulo ?? null,
                'modalidade' => $aula->modalidade,
                'local' => $ehOnline ? null : $aula->local,
                'duracao' => $aula->duracao,
                'status' => $aula->status,
                'observacoes' => $aula->observacoes,
            ]);
        }

        foreach ($eventosSecretaria as $evento) {
            $inicio = Carbon::parse($evento->data_inicio);
            $fim = $evento->data_fim ? Carbon::parse($evento->data_fim) : null;

            $eventos->push([
                'id' => 'secretaria-'.$evento->id,
                'title' => $evento->titulo,
                'start' => $inicio->toIso8601String(),
                'end' => $fim?->toIso8601String(),
                'type' => 'secretaria',
                'url' => null,
                'color' => $evento->cor ?: '#7c3aed',
                'description' => $evento->descricao,
                'turma' => $evento->turma->nome ?? null,
                'curso' => $evento->curso->titulo ?? null,
                'modalidade' => null,
                'local' => null,
                'duracao' => null,
                'status' => null,
                'observacoes' => null,
            ]);
        }

        foreach ($avisos as $aviso) {
            if (! $aviso->data_evento) {
                continue;
            }

            $eventos->push([
                'id' => 'aviso-'.$aviso->id,
                'title' => $aviso->titulo,
                'start' => Carbon::parse($aviso->data_evento)->format('Y-m-d'),
                'end' => null,
                'allDay' => true,
                'type' => 'aviso',
                'url' => null,
                'color' => '#f59e0b',
                'description' => $aviso->mensagem,
                'turma' => null,
                'curso' => null,
                'modalidade' => null,
                'local' => null,
                'duracao' => null,
                'status' => null,
                'observacoes' => null,
            ]);
        }

        return $eventos
            ->sortBy('start')
            ->values()
            ->all();
    }
}
