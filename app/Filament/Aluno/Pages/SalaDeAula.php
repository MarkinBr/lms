<?php

namespace App\Filament\Aluno\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class SalaDeAula extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationLabel = 'Sala de Aula';
    protected static ?string $navigationGroup = 'Ensino';
    
    // Mantendo o nome da view original
    protected static string $view = 'filament.aluno.pages.sala-de-aula';
    
    protected static ?int $navigationSort = 1;
    protected static bool $shouldRegisterNavigation = false;

    public $aulaId;
    public $aula;
    public $modulo;
    public $curso;
    public $todasAulas = [];
    public $progresso = [];
    public $proximaAula = null;
    public $anteriorAula = null;
    public $comentarios = [];
    public $novoComentario = '';

    // Progresso do curso
    public $progressoCurso = 0;
    public $aulasConcluidasCurso = 0;
    public $totalAulasCurso = 0;
    public $numeroAulaAtual = 0;

    public function mount(): void
    {
        $this->aulaId = request()->get('aula');

        if (!$this->aulaId) {
            redirect('/aluno/meus-cursos');
            return;
        }

        $this->carregarDados();
    }

    protected function carregarDados(): void
    {
        $this->aula = DB::table('aulas')->where('id', $this->aulaId)->first();
        if (!$this->aula) {
            return;
        }

        $this->modulo = DB::table('modulos')->where('id', $this->aula->modulo_id)->first();
        $this->curso  = DB::table('cursos')->where('id', $this->modulo->curso_id)->first();

        $matriculado = DB::table('matriculas')
            ->where('user_id', Auth::id())
            ->where('curso_id', $this->curso->id)
            ->exists();

        if (!$matriculado) {
            redirect('/aluno/meus-cursos');
            return;
        }

        $todasAulasFlat = DB::table('aulas')
            ->join('modulos', 'modulos.id', '=', 'aulas.modulo_id')
            ->where('modulos.curso_id', $this->curso->id)
            ->orderBy('modulos.ordem')
            ->orderBy('aulas.ordem')
            ->select('aulas.*', 'modulos.titulo as modulo_titulo', 'modulos.id as modulo_id')
            ->get();

        $this->todasAulas = $todasAulasFlat->groupBy('modulo_id');
        $this->totalAulasCurso = $todasAulasFlat->count();
        $this->numeroAulaAtual = $todasAulasFlat->search(fn($a) => $a->id == $this->aulaId) + 1;

        $progressoData = DB::table('aula_progresso')
            ->where('aluno_id', Auth::id())
            ->whereIn('aula_id', $todasAulasFlat->pluck('id'))
            ->pluck('concluida', 'aula_id')
            ->toArray();

        $this->progresso = $progressoData;

        $this->aulasConcluidasCurso = collect($progressoData)
            ->filter(fn($c) => (int) $c === 1)
            ->count();

        $this->progressoCurso = $this->totalAulasCurso > 0
            ? ($this->aulasConcluidasCurso / $this->totalAulasCurso) * 100
            : 0;

        $indiceAtual = $todasAulasFlat->search(fn($a) => $a->id == $this->aulaId);
        $this->proximaAula = $todasAulasFlat->get($indiceAtual + 1);
        $this->anteriorAula = $todasAulasFlat->get($indiceAtual - 1);

        $this->comentarios = DB::table('comentarios_aula')
            ->join('users', 'users.id', '=', 'comentarios_aula.user_id')
            ->where('comentarios_aula.aula_id', $this->aulaId)
            ->orderBy('comentarios_aula.created_at', 'desc')
            ->select('comentarios_aula.*', 'users.name', 'users.foto')
            ->get();

        DB::table('aula_progresso')->updateOrInsert(
            ['aluno_id' => Auth::id(), 'aula_id' => $this->aulaId],
            ['ultima_visualizacao' => now(), 'updated_at' => now()]
        );
    }

    public function marcarComoConcluida(): void
    {
        DB::table('aula_progresso')->updateOrInsert(
            ['aluno_id' => Auth::id(), 'aula_id' => $this->aulaId],
            ['concluida' => true, 'updated_at' => now()]
        );

        $this->progresso[$this->aulaId] = true;
        $this->aulasConcluidasCurso++;
        $this->progressoCurso = $this->totalAulasCurso > 0
            ? ($this->aulasConcluidasCurso / $this->totalAulasCurso) * 100
            : 0;

        Notification::make()
            ->title('Parabéns! 🎉')
            ->body('Aula marcada como concluída!')
            ->success()
            ->send();
    }

    public function navegarParaAula($aulaId): void
    {
        $this->redirect("/aluno/sala-de-aula?aula={$aulaId}");
    }

    public function voltarParaCursos(): void
    {
        $this->redirect('/aluno/meus-cursos');
    }

    public function enviarComentario(): void
    {
        if (empty(trim($this->novoComentario))) {
            Notification::make()
                ->title('Atenção')
                ->body('Digite um comentário antes de enviar.')
                ->warning()
                ->send();
            return;
        }

        DB::table('comentarios_aula')->insert([
            'aula_id' => $this->aulaId,
            'user_id' => Auth::id(),
            'comentario' => $this->novoComentario,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->novoComentario = '';
        $this->carregarDados();

        Notification::make()
            ->title('Sucesso!')
            ->body('Comentário enviado com sucesso!')
            ->success()
            ->send();
    }

    public function getTitle(): string | Htmlable
    {
        return $this->aula ? $this->aula->titulo : 'Sala de Aula';
    }
}