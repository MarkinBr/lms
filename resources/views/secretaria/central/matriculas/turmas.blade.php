@extends('secretaria.central.layout')
@section('title', 'Gestão de Turmas')
@section('breadcrumb')<span aria-hidden="true">/</span><a href="{{ route('secretaria.matriculas.painel') }}">Matrículas e turmas</a><span aria-hidden="true">/</span><span aria-current="page">Turmas</span>@endsection
@section('content')
    <div class="sc-intro">
        <div><p class="sc-eyebrow">Organização acadêmica</p><h1>Gestão de turmas</h1>
            <p>Organize períodos, professores, disciplinas e vagas. Consulte os alunos antes de qualquer movimentação acadêmica.</p></div>
        <a class="sc-button sc-primary" href="{{ route('filament.secretario.resources.turma-secretarias.create') }}">@include('secretaria.central.icone',['nome'=>'mais']) Nova turma</a>
    </div>
    @include('secretaria.central.matriculas.nav', ['secao' => 'turmas'])
    <section class="sc-panel">
        <div class="sc-panel-heading">
            <h2>Filtrar turmas</h2><p>Consulte a situação acadêmica e o calendário de inscrições separadamente.</p>
            <form class="sc-ac-filter" action="{{ route('secretaria.matriculas.turmas') }}" method="get" role="search" style="grid-template-columns:minmax(170px,1fr) repeat(2,minmax(130px,auto)) auto">
                <label for="turma-busca" class="sc-sr-only">Nome, código, disciplina ou professor</label>
                <input id="turma-busca" name="busca" value="{{ $busca }}" type="search" maxlength="150" placeholder="Nome, código ou professor...">
                <label for="turma-curso" class="sc-sr-only">Curso</label>
                <select name="curso" id="turma-curso"><option value="">Todos os cursos</option>
                    @foreach($cursos as $curso)<option value="{{ $curso->id }}" @selected($cursoId === $curso->id)>{{ $curso->titulo }}</option>@endforeach
                </select>
                <label for="turma-situacao" class="sc-sr-only">Situação</label>
                <select id="turma-situacao" name="situacao">
                    <option value="">Todas as situações</option>
                    @foreach(\App\Models\Turma::SITUACOES_ACADEMICAS as $valor => $rotulo)
                        <option value="{{ $valor }}" @selected($situacao === $valor)>{{ $rotulo }}</option>
                    @endforeach
                </select>
                <button type="submit" class="sc-button sc-primary">Buscar</button>
            </form>
            @if($busca!=='' || $cursoId || $situacao!=='')<a class="sc-text-link" href="{{ route('secretaria.matriculas.turmas') }}">Limpar filtros</a>@endif
        </div>
    </section>
    <div class="sc-ac-section-title">
        <h2>{{ number_format($turmas->total(),0,',','.') }} {{ $turmas->total()===1 ? 'turma encontrada' : 'turmas encontradas' }}</h2>
        <span class="sc-ac-badge is-neutral">Vagas calculadas por matrículas ativas distintas</span>
    </div>
    <div class="sc-ac-class-grid">
        @forelse($turmas as $turma)
            @php
                $ocupacao = (int)$turma->ocupacao_ativa;
                $capacidade = $turma->capacidade !== null ? (int)$turma->capacidade : null;
                $vagas = $capacidade !== null ? max(0,$capacidade-$ocupacao) : null;
                $percentual = $capacidade && $capacidade>0 ? min(100,round(($ocupacao/$capacidade)*100)) : 0;
                $classe = $turma->situacao_academica === 'em_andamento' ? 'is-green' : ($turma->situacao_academica === 'planejada' ? 'is-amber' : 'is-neutral');
            @endphp
            <article class="sc-ac-class">
                <div class="sc-ac-class-top">
                    <span class="sc-ac-class-code">{{ $turma->codigo ?: 'Sem código' }} · {{ $turma->periodoLetivo?->nome ?? $turma->ano_letivo ?? 'Período não informado' }}</span>
                    <span class="sc-ac-badge {{ $classe }}">{{ \App\Models\Turma::SITUACOES_ACADEMICAS[$turma->situacao_academica] ?? 'Sem situação' }}</span>
                </div>
                <h3>{{ $turma->nome }}</h3>
                <p>{{ $turma->curso?->titulo ?? 'Curso não informado' }} · {{ ($turma->disciplinas_count ?? 0) }} disciplina(s) vinculada(s)</p>
                <dl class="sc-ac-class-details">
                    <div><dt>Atribuições docentes</dt><dd>{{ $turma->professor_atribuicoes_count ?? 0 }} designação(ões)</dd></div>
                    <div><dt>Inscrições</dt><dd>{{ \App\Models\Turma::SITUACOES_INSCRICOES[$turma->situacao_inscricoes] ?? 'Não definidas' }}</dd></div>
                    <div><dt>Encontro</dt><dd>{{ match($turma->turno) {
                        '1_sabado'=>'1º sábado','2_sabado'=>'2º sábado','3_sabado'=>'3º sábado','4_sabado'=>'4º sábado',
                        default=>$turma->turno ?? 'Não definido',
                    } }}</dd></div>
                    <div><dt>Modalidade</dt><dd>{{ \App\Models\Turma::MODALIDADE_LABELS[$turma->modalidade] ?? 'Não definida' }}</dd></div>
                    <div><dt>Período</dt><dd>{{ $turma->periodoLetivo?->nome ?? $turma->ano_letivo ?? 'Não definido' }}</dd></div>
                </dl>
                <div class="sc-ac-occupancy">
                    <div class="sc-ac-occupancy-line"><span>Matrículas ativas na turma</span>
                        <strong>{{ $ocupacao }} {{ $capacidade!==null ? 'de '.$capacidade.' vagas' : '· sem limite definido' }}</strong>
                    </div>
                    @if($capacidade!==null)
                        <div class="sc-ac-bar" role="img" aria-label="{{ $percentual }}% de ocupação"><span style="width: {{ $percentual }}%"></span></div>
                    @endif
                    <div class="sc-ac-class-foot">
                        <span class="sc-ac-badge {{ $vagas===0 ? 'is-amber' : 'is-green' }}">
                            {{ $vagas===null ? 'Capacidade não definida' : ($vagas>0 ? $vagas.' vagas disponíveis' : 'Sem vagas') }}
                        </span>
                        <a class="sc-button sc-button-small" href="{{ route('secretaria.matriculas.turma',$turma) }}">Ver turma @include('secretaria.central.icone',['nome'=>'seta'])</a>
                    </div>
                </div>
            </article>
        @empty
            <section class="sc-panel"><div class="sc-panel-body"><h3>Nenhuma turma encontrada</h3><p class="sc-ac-muted">Altere os filtros para localizar outras turmas.</p></div></section>
        @endforelse
    </div>
    <div class="sc-panel-body">{{ $turmas->links() }}</div>
@endsection
