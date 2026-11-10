@extends('secretaria.central.layout')
@section('title', 'Ficha da turma')
@section('breadcrumb')
    <span aria-hidden="true">/</span><a href="{{ route('secretaria.matriculas.painel') }}">Matrículas e turmas</a>
    <span aria-hidden="true">/</span><a href="{{ route('secretaria.matriculas.turmas') }}">Turmas</a>
    <span aria-hidden="true">/</span><span aria-current="page">Ficha da turma</span>
@endsection
@section('content')
    <a class="sc-back" href="{{ route('secretaria.matriculas.turmas') }}">@include('secretaria.central.icone',['nome'=>'voltar']) Voltar às turmas</a>
    <div class="sc-intro">
        <div><p class="sc-eyebrow">Organização de turmas</p><h1>{{ $turma->nome }}</h1>
            <p>Visualize os alunos vinculados, os dados do período e a ocupação antes de adicionar ou transferir estudantes.</p></div>
        <a class="sc-button" href="{{ route('filament.secretario.resources.turma-secretarias.edit',$turma) }}">@include('secretaria.central.icone',['nome'=>'editar']) Editar dados da turma</a>
    </div>
    @include('secretaria.central.matriculas.nav',['secao'=>'turmas'])
    @php
        $capacidade = $turma->capacidade !== null ? (int)$turma->capacidade : null;
        $vagas = $capacidade!==null ? max(0,$capacidade-$ocupacaoAtiva) : null;
        $percentual = $capacidade && $capacidade>0 ? min(100,round(($ocupacaoAtiva/$capacidade)*100)) : 0;
    @endphp
    <section class="sc-profile-hero" style="margin-bottom:22px">
        <span class="sc-avatar sc-avatar-large" aria-hidden="true">@include('secretaria.central.icone',['nome'=>'pessoas'])</span>
        <div class="sc-profile-person">
            <h2>{{ $turma->codigo ?: 'Turma sem código' }}</h2>
            <p>{{ $turma->curso?->titulo ?? 'Curso não informado' }} · {{ $turma->periodoLetivo?->nome ?? $turma->ano_letivo ?? 'Período não informado' }}</p>
            <div class="sc-badges" style="margin-top:8px">
                <span class="sc-ac-badge is-green">{{ \App\Models\Turma::STATUS_LABELS[$turma->status] ?? 'Sem situação' }}</span>
                <span class="sc-ac-badge is-neutral">{{ \App\Models\Turma::MODALIDADE_LABELS[$turma->modalidade] ?? 'Modalidade não definida' }}</span>
            </div>
        </div>
        <div class="sc-profile-context">
            <dl><div><dt>Ocupação atual</dt><dd><strong>{{ $ocupacaoAtiva }} {{ $capacidade!==null ? 'de '.$capacidade : 'matrículas ativas' }}</strong></dd></div></dl>
            @if($capacidade!==null)<div class="sc-ac-bar"><span style="width:{{ $percentual }}%"></span></div>@endif
        </div>
    </section>
    <div class="sc-ac-split">
        <div class="sc-stack">
            <section class="sc-panel" aria-labelledby="alunos-turma">
                <div class="sc-panel-heading">
                    <h2 id="alunos-turma">Alunos vinculados e histórico</h2>
                    <p>Identifica os vínculos ativos e anteriores, incluindo transferências.</p>
                </div>
                <div class="sc-ac-table-wrap">
                    <table class="sc-ac-history">
                        <thead><tr><th>Aluno</th><th>Número acadêmico</th><th>Vínculo com esta turma</th><th>Ficha</th></tr></thead>
                        <tbody>
                            @forelse($matriculas as $matricula)
                                @php
                                    $pivot = $matricula->turmasHistorico->first()?->pivot;
                                    $ativo = (int)$matricula->turma_id === (int)$turma->id || $pivot?->status === \App\Models\MatriculaTurma::STATUS_ATIVO;
                                    $statusVinculo = $ativo ? 'Ativo' : (\App\Models\MatriculaTurma::STATUS_LABELS[$pivot?->status] ?? 'Histórico');
                                @endphp
                                <tr>
                                    <td><strong>{{ $matricula->user?->name ?? 'Aluno não informado' }}</strong><small>{{ ucfirst($matricula->status ?? 'Sem situação') }}</small></td>
                                    <td class="sc-ac-mono">{{ $matricula->numero_matricula ?: 'Não informado' }}</td>
                                    <td><span class="sc-ac-badge {{ $ativo ? 'is-green' : 'is-neutral' }}">{{ $statusVinculo }}</span></td>
                                    <td><a class="sc-button sc-button-small" href="{{ route('secretaria.matriculas.show',$matricula) }}">Abrir ficha →</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="4">Nenhuma matrícula vinculada à turma.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="sc-panel-body">{{ $matriculas->links() }}</div>
            </section>
            <section class="sc-panel">
                <div class="sc-panel-heading"><h2>Configuração da turma</h2><p>Dados essenciais para a organização do ensino.</p></div>
                <dl class="sc-data-grid" style="padding:22px">
                    <div><dt>Curso</dt><dd>{{ $turma->curso?->titulo ?? 'Não definido' }}</dd></div>
                    <div><dt>Disciplina</dt><dd>{{ $turma->disciplina?->nome ?? 'Não definida' }}</dd></div>
                    <div><dt>Professor(a)</dt><dd>{{ $turma->professor?->name ?? 'Não atribuído' }}</dd></div>
                    <div><dt>Período letivo</dt><dd>{{ $turma->periodoLetivo?->nome ?? 'Não informado' }}</dd></div>
                    <div><dt>Dia de encontro</dt><dd>{{ match($turma->turno) {
                        '1_sabado'=>'1º sábado','2_sabado'=>'2º sábado','3_sabado'=>'3º sábado','4_sabado'=>'4º sábado',
                        default=>$turma->turno ?? 'Não definido',
                    } }}</dd></div>
                    <div><dt>Início</dt><dd>{{ $turma->data_inicio ? \Illuminate\Support\Carbon::parse($turma->data_inicio)->format('d/m/Y') : 'Não definido' }}</dd></div>
                    <div><dt>Término</dt><dd>{{ $turma->data_fim ? \Illuminate\Support\Carbon::parse($turma->data_fim)->format('d/m/Y') : 'Não definido' }}</dd></div>
                    <div><dt>Capacidade</dt><dd>{{ $capacidade ?? 'Não definida' }}</dd></div>
                    <div><dt>Vagas restantes</dt><dd>{{ $vagas ?? 'Sem limite definido' }}</dd></div>
                </dl>
            </section>
        </div>
        <aside class="sc-aside">
            <section class="sc-side-card sc-side-gold">
                <h2>Operações da turma</h2>
                <p>Para adicionar um aluno, localize uma matrícula ativa do mesmo curso e abra sua ficha para registrar o vínculo.</p>
                <a class="sc-button sc-primary" style="width:100%;margin-top:14px"
                    href="{{ route('secretaria.matriculas.index',['curso'=>$turma->curso_id,'situacao'=>'ativas']) }}">
                    @include('secretaria.central.icone',['nome'=>'mais']) Localizar matrícula
                </a>
                @if($turma->status !== \App\Models\Turma::STATUS_ABERTA)
                    <p class="sc-ac-muted" style="margin-top:12px">Esta turma não está aberta para novos vínculos.</p>
                @elseif($vagas===0)
                    <p class="sc-ac-muted" style="margin-top:12px">A turma atingiu sua capacidade.</p>
                @endif
            </section>
            <section class="sc-side-card"><h2>Contagem correta de vagas</h2>
                <p>Contamos matrículas ativas distintas, considerando vínculos legados e acadêmicos. Matrículas trancadas e vínculos anteriores continuam no histórico, mas não ocupam vaga ativa.</p>
            </section>
        </aside>
    </div>
@endsection
