@extends('secretaria.central.layout')
@section('title', 'Gestão de Matrículas')
@section('breadcrumb')<span aria-hidden="true">/</span><a href="{{ route('secretaria.matriculas.painel') }}">Matrículas e turmas</a><span aria-hidden="true">/</span><span aria-current="page">Matrículas</span>@endsection
@section('content')
    <div class="sc-intro">
        <div>
            <p class="sc-eyebrow">Gestão acadêmica</p><h1>Gestão de matrículas</h1>
            <p>Cada linha representa a matrícula de um aluno em um curso. Uma pessoa pode aparecer mais de uma vez quando estuda em formações diferentes.</p>
        </div>
        <a class="sc-button sc-primary" href="{{ route('filament.secretario.resources.matricula-secretarias.create') }}">@include('secretaria.central.icone',['nome'=>'mais']) Nova matrícula</a>
    </div>
    @include('secretaria.central.matriculas.nav', ['secao' => 'matriculas'])
    <div class="sc-ac-split">
        <section class="sc-panel" aria-labelledby="lista-matriculas">
            <div class="sc-panel-heading">
                <h2 id="lista-matriculas">Localizar matrícula acadêmica</h2>
                <p>Pesquise por aluno ou número e refine por situação, curso e turma.</p>
                <form action="{{ route('secretaria.matriculas.index') }}" method="get" role="search" class="sc-ac-filter">
                    <label class="sc-sr-only" for="mat-busca">Pesquisar matrícula</label>
                    <input id="mat-busca" name="busca" type="search" maxlength="150" value="{{ $busca }}" placeholder="Aluno ou número...">
                    <label class="sc-sr-only" for="mat-curso">Curso</label>
                    <select name="curso" id="mat-curso"><option value="">Todos os cursos</option>
                        @foreach($cursos as $curso)<option value="{{ $curso->id }}" @selected($cursoId === $curso->id)>{{ $curso->titulo }}</option>@endforeach
                    </select>
                    <label class="sc-sr-only" for="mat-situacao">Situação</label>
                    <select name="situacao" id="mat-situacao">
                        @foreach([''=>'Todas as situações','ativas'=>'Ativas','trancadas'=>'Trancadas','concluidas'=>'Concluídas','canceladas'=>'Canceladas'] as $valor=>$rotulo)
                            <option value="{{ $valor }}" @selected($situacao === $valor)>{{ $rotulo }}</option>
                        @endforeach
                    </select>
                    <label class="sc-sr-only" for="mat-turma">Turma</label>
                    <select name="turma" id="mat-turma"><option value="">Todas as turmas</option>
                        <option value="sem" @selected($turmaId === 'sem')>Sem turma</option>
                        @foreach($turmasFiltro as $turma)<option value="{{ $turma->id }}" @selected($turmaId === (string)$turma->id)>{{ $turma->nome }}</option>@endforeach
                    </select>
                    <button class="sc-button sc-primary" type="submit">Buscar</button>
                </form>
                @if($busca!=='' || $cursoId || $situacao!=='' || $turmaId!=='')
                    <a class="sc-text-link" href="{{ route('secretaria.matriculas.index') }}">Limpar filtros</a>
                @endif
            </div>
            <div class="sc-list-label">
                <span>{{ number_format($matriculas->total(),0,',','.') }} {{ $matriculas->total()===1 ? 'matrícula encontrada' : 'matrículas encontradas' }}</span>
                <span>Vínculo acadêmico</span>
            </div>
            <ul class="sc-ac-list">
                @forelse($matriculas as $matricula)
                    @php
                        $statusNormalizado = mb_strtolower((string)$matricula->status);
                        $classeStatus = in_array($statusNormalizado,['ativo','ativa'],true) ? 'is-green' :
                            (in_array($statusNormalizado,['trancado','trancada'],true) ? 'is-amber' :
                            (in_array($statusNormalizado,['cancelado','cancelada'],true) ? 'is-red' : 'is-neutral'));
                        $turmasAtivas = $matricula->turmasHistorico->filter(fn($t) => $t->pivot->status === \App\Models\MatriculaTurma::STATUS_ATIVO);
                        $semTurma = !$matricula->turma_id && $turmasAtivas->isEmpty();
                    @endphp
                    <li class="sc-ac-item">
                        <div class="sc-ac-item-body">
                            <span class="sc-avatar" aria-hidden="true">@include('secretaria.central.icone',['nome'=>'pessoas'])</span>
                            <div>
                                <h3>{{ $matricula->user?->name ?? 'Aluno não vinculado' }} <span class="sc-ac-mono">· {{ $matricula->numero_matricula ?: 'Sem número' }}</span></h3>
                                <p><strong>{{ $matricula->curso?->titulo ?? 'Curso não informado' }}</strong> ·
                                    {{ $turmasAtivas->pluck('nome')->take(2)->implode(', ') ?: ($matricula->turma?->nome ?? 'Sem turma') }}
                                    @if($turmasAtivas->count()>2) +{{ $turmasAtivas->count()-2 }} @endif
                                </p>
                                <div class="sc-badges">
                                    <span class="sc-ac-badge {{ $classeStatus }}">{{ ucfirst($matricula->status ?? 'Sem situação') }}</span>
                                    @if($semTurma && in_array($statusNormalizado,['ativo','ativa'],true))
                                        <span class="sc-ac-badge is-red">Precisa de turma</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <a class="sc-button sc-button-small" href="{{ route('secretaria.matriculas.show',$matricula) }}">Abrir ficha @include('secretaria.central.icone',['nome'=>'seta'])</a>
                    </li>
                @empty
                    <li class="sc-empty"><h3>Nenhuma matrícula encontrada</h3><p>Altere os filtros para localizar outros registros.</p></li>
                @endforelse
            </ul>
            <div class="sc-panel-body">{{ $matriculas->links() }}</div>
        </section>
        <aside class="sc-aside" aria-label="Orientações">
            <section class="sc-side-card"><h2>Visão das matrículas</h2><dl class="sc-summary">
                <div><dt>Total geral</dt><dd>{{ number_format($totais['matriculas'],0,',','.') }}</dd></div>
                <div><dt>Ativas</dt><dd>{{ number_format($totais['ativas'],0,',','.') }}</dd></div>
                <div><dt>Sem turma ativa</dt><dd>{{ number_format($totais['sem_turma'],0,',','.') }}</dd></div>
                <div><dt>Turmas cadastradas</dt><dd>{{ number_format($totais['turmas'],0,',','.') }}</dd></div>
            </dl></section>
            <section class="sc-side-card sc-side-gold"><h2>Corrigir número acadêmico</h2><p>A correção fica na ficha da matrícula, com justificativa, senha administrativa e histórico. Não altera o login.</p></section>
            <section class="sc-side-card"><h2>Regra de organização</h2><p>Uma matrícula pertence a um curso e pode reunir vínculos com várias turmas de disciplinas diferentes.</p></section>
        </aside>
    </div>
@endsection
