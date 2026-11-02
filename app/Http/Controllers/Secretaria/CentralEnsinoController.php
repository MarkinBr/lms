<?php

namespace App\Http\Controllers\Secretaria;

use App\Http\Controllers\Controller;
use App\Models\Aula;
use App\Models\BancoQuestao;
use App\Models\ChamadaFrequencia;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\InstrumentoAvaliacao;
use App\Models\Modulo;
use App\Models\Nota;
use App\Models\Progresso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CentralEnsinoController extends Controller
{
    private function catalogo(): array
    {
        return [
            'cursos' => Curso::query()->orderBy('titulo')->get(['id', 'titulo']),
            'disciplinas' => Disciplina::query()->with('curso:id,titulo')->orderBy('nome')->get(['id', 'curso_id', 'modulo_id', 'nome']),
            'modulos' => Modulo::query()->orderBy('ordem')->get(['id', 'curso_id', 'titulo']),
        ];
    }

    private function filtros(Request $request): array
    {
        return [
            'busca' => mb_substr(trim((string) $request->query('busca', '')), 0, 120),
            'curso' => filter_var($request->query('curso'), FILTER_VALIDATE_INT) ?: null,
            'disciplina' => filter_var($request->query('disciplina'), FILTER_VALIDATE_INT) ?: null,
        ];
    }

    public function painel(): View
    {
        $totais = [
            'modulos' => Modulo::query()->count(),
            'disciplinas' => Disciplina::query()->count(),
            'aulas' => Aula::query()->count(),
            'nao_publicadas' => Aula::query()->where('liberada', false)->count(),
            'chamadas_pendentes' => ChamadaFrequencia::query()->rascunhos()->count(),
            'questoes_rascunho' => BancoQuestao::query()->where('status', 'rascunho')->count(),
        ];
        $aulasPendentes = Aula::query()->where('liberada', false)
            ->with('disciplina:id,nome')->latest('id')->limit(6)->get();
        $chamadasPendentes = ChamadaFrequencia::query()->rascunhos()
            ->with(['turma:id,nome', 'disciplina:id,nome'])->latest('id')->limit(6)->get();

        return view('secretaria.central.ensino.painel', compact('totais', 'aulasPendentes', 'chamadasPendentes'));
    }

    public function modulos(Request $request): View
    {
        $f = $this->filtros($request);
        $modulos = Modulo::query()->with('curso:id,titulo')->withCount('disciplinas')
            ->when($f['curso'], fn ($q) => $q->where('curso_id', $f['curso']))
            ->when($f['busca'] !== '', fn ($q) => $q->whereRaw('LOWER(titulo) LIKE ?', ['%'.mb_strtolower($f['busca']).'%']))
            ->orderBy('curso_id')->orderBy('ordem')->paginate(18)->withQueryString();

        return view('secretaria.central.ensino.modulos', ['modulosLista' => $modulos, 'f' => $f] + $this->catalogo());
    }

    public function disciplinas(Request $request): View
    {
        $f = $this->filtros($request);
        $disciplinasLista = Disciplina::query()->with(['curso:id,titulo', 'modulo:id,titulo'])
            ->withCount('aulas')
            ->when($f['curso'], fn ($q) => $q->where('curso_id', $f['curso']))
            ->when($f['busca'] !== '', fn ($q) => $q->whereRaw('LOWER(nome) LIKE ?', ['%'.mb_strtolower($f['busca']).'%']))
            ->orderBy('curso_id')->orderBy('ordem')->paginate(18)->withQueryString();

        return view('secretaria.central.ensino.disciplinas', ['disciplinasLista' => $disciplinasLista, 'f' => $f] + $this->catalogo());
    }

    public function aulas(Request $request): View
    {
        $f = $this->filtros($request);
        $aulasLista = Aula::query()->with(['disciplina:id,nome,curso_id,modulo_id', 'modulo:id,titulo'])
            ->when($f['disciplina'], fn ($q) => $q->where('disciplina_id', $f['disciplina']))
            ->when($f['curso'], fn ($q) => $q->whereHas('disciplina', fn ($d) => $d->where('curso_id', $f['curso'])))
            ->when($f['busca'] !== '', fn ($q) => $q->whereRaw('LOWER(titulo) LIKE ?', ['%'.mb_strtolower($f['busca']).'%']))
            ->orderByDesc('id')->paginate(15)->withQueryString();

        return view('secretaria.central.ensino.aulas', ['aulasLista' => $aulasLista, 'f' => $f] + $this->catalogo());
    }

    public function aula(?Aula $aula = null): View
    {
        if ($aula?->exists) {
            $aula->load('disciplina.curso', 'modulo');
        }

        return view('secretaria.central.ensino.aula', ['aula' => $aula] + $this->catalogo());
    }

    public function notas(Request $request): View
    {
        $busca = mb_substr(trim((string) $request->query('busca', '')), 0, 120);
        $notas = Nota::query()->with('aluno:id,name')
            ->when($busca !== '', fn ($q) => $q->whereHas('aluno', fn ($u) =>
                $u->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($busca).'%']))
                ->orWhereRaw('LOWER(disciplina) LIKE ?', ['%'.mb_strtolower($busca).'%']))
            ->orderByDesc('id')->paginate(20)->withQueryString();

        return view('secretaria.central.ensino.notas', compact('notas', 'busca'));
    }

    public function frequencias(Request $request): View
    {
        $busca = mb_substr(trim((string) $request->query('busca', '')), 0, 120);
        $chamadas = ChamadaFrequencia::query()
            ->with(['turma:id,nome', 'disciplina:id,nome'])->withCount('frequencias')
            ->when($busca !== '', fn ($q) => $q->whereHas('turma', fn ($t) =>
                $t->whereRaw('LOWER(nome) LIKE ?', ['%'.mb_strtolower($busca).'%'])))
            ->orderByDesc('data_aula')->paginate(18)->withQueryString();

        return view('secretaria.central.ensino.frequencias', compact('chamadas', 'busca'));
    }

    public function ead(Request $request): View
    {
        $busca = mb_substr(trim((string) $request->query('busca', '')), 0, 120);
        $registros = Progresso::query()->with(['user:id,name', 'aula:id,titulo,disciplina_id'])
            ->when($busca !== '', fn ($q) => $q->whereHas('user', fn ($u) =>
                $u->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($busca).'%'])))
            ->orderByDesc('id')->paginate(20)->withQueryString();

        return view('secretaria.central.ensino.ead', compact('registros', 'busca'));
    }

    public function avaliacoes(Request $request): View
    {
        $tipo = (string) $request->query('tipo', '');
        $instrumentos = InstrumentoAvaliacao::query()->with(['disciplina:id,nome', 'aula:id,titulo'])
            ->withCount('questoes')
            ->when(in_array($tipo, ['quiz', 'prova', 'avaliacao', 'simulado'], true), fn ($q) => $q->where('tipo', $tipo))
            ->orderByDesc('id')->paginate(18)->withQueryString();
        $totais = InstrumentoAvaliacao::query()->select('tipo', DB::raw('count(*) as total'))
            ->groupBy('tipo')->pluck('total', 'tipo');

        return view('secretaria.central.ensino.avaliacoes', ['instrumentos' => $instrumentos, 'tipo' => $tipo, 'totais' => $totais] + $this->catalogo());
    }

    public function questoes(Request $request): View
    {
        $f = $this->filtros($request);
        $dificuldade = (string) $request->query('dificuldade', '');
        $questoes = BancoQuestao::query()->with('disciplina:id,nome')
            ->when($f['disciplina'], fn ($q) => $q->where('disciplina_id', $f['disciplina']))
            ->when(in_array($dificuldade, ['facil', 'medio', 'dificil'], true), fn ($q) => $q->where('dificuldade', $dificuldade))
            ->when($f['busca'] !== '', fn ($q) => $q->whereRaw('LOWER(enunciado) LIKE ?', ['%'.mb_strtolower($f['busca']).'%']))
            ->orderByDesc('id')->paginate(18)->withQueryString();

        return view('secretaria.central.ensino.questoes', [
            'questoesLista' => $questoes, 'f' => $f, 'dificuldade' => $dificuldade,
        ] + $this->catalogo());
    }

    public function instrumento(InstrumentoAvaliacao $instrumento): View
    {
        $instrumento->load(['disciplina:id,nome', 'aula:id,titulo', 'questoes']);

        return view('secretaria.central.ensino.instrumento', ['instrumento' => $instrumento] + $this->catalogo());
    }
}
