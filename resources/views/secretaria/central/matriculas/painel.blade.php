@extends('secretaria.central.layout')
@section('title', 'Gestão de Matrículas e Turmas')
@section('breadcrumb')<span aria-hidden="true">/</span><span aria-current="page">Matrículas e turmas</span>@endsection
@section('content')
    <div class="sc-intro">
        <div>
            <p class="sc-eyebrow">Gestão acadêmica</p>
            <h1>Matrículas e turmas</h1>
            <p>Uma mesa de trabalho para localizar o aluno, entender sua situação acadêmica e resolver os próximos passos — sem misturar o acesso ao portal com a matrícula.</p>
        </div>
        <div class="sc-ac-button-row">
            <a class="sc-button sc-primary" href="{{ route('filament.secretario.resources.matricula-secretarias.create') }}">@include('secretaria.central.icone', ['nome'=>'mais']) Nova matrícula</a>
            <a class="sc-button" href="{{ route('filament.secretario.resources.turma-secretarias.create') }}">@include('secretaria.central.icone', ['nome'=>'mais']) Nova turma</a>
        </div>
    </div>
    @include('secretaria.central.matriculas.nav', ['secao' => 'painel'])
    <dl class="sc-ac-metrics">
        <div class="sc-ac-metric"><dt>Matrículas ativas</dt><dd>{{ number_format($totais['matriculas_ativas'],0,',','.') }}</dd><small>Em cursos do CETSADP</small></div>
        <div class="sc-ac-metric"><dt>Alunos sem turma</dt><dd>{{ number_format($totais['sem_turma'],0,',','.') }}</dd><a href="{{ route('secretaria.matriculas.index', ['situacao'=>'ativas','turma'=>'sem']) }}">Resolver pendências</a></div>
        <div class="sc-ac-metric"><dt>Turmas abertas</dt><dd>{{ number_format($totais['turmas_abertas'],0,',','.') }}</dd><a href="{{ route('secretaria.matriculas.turmas',['situacao'=>'aberta']) }}">Consultar turmas</a></div>
        <div class="sc-ac-metric"><dt>Turmas sem vagas</dt><dd>{{ number_format($totais['turmas_lotadas'],0,',','.') }}</dd><a href="{{ route('secretaria.matriculas.turmas',['situacao'=>'aberta']) }}">Conferir ocupação</a></div>
    </dl>
    <div class="sc-ac-split">
        <div class="sc-stack">
            <section class="sc-panel" aria-labelledby="pendencias-titulo">
                <div class="sc-panel-heading">
                    <h2 id="pendencias-titulo">Pendências que exigem atendimento</h2>
                    <p>Acompanhe as matrículas que precisam de uma turma e as situações acadêmicas que merecem atenção.</p>
                </div>
                @forelse ($pendencias as $matricula)
                    <div class="sc-ac-row">
                        <span class="sc-ac-emblem" aria-hidden="true">@include('secretaria.central.icone',['nome'=>'aviso'])</span>
                        <div><h3>{{ $matricula->user?->name ?? 'Aluno não identificado' }} · sem turma</h3>
                            <p>{{ $matricula->curso?->titulo ?? 'Curso não informado' }} · Nº {{ $matricula->numero_matricula ?: 'Não informado' }}</p></div>
                        <a class="sc-button sc-button-small" href="{{ route('secretaria.matriculas.show',$matricula) }}">Resolver →</a>
                    </div>
                @empty
                    <div class="sc-panel-body"><p class="sc-ac-muted">Nenhuma matrícula ativa sem turma identificada.</p></div>
                @endforelse
                @foreach ($turmasLotadas as $turma)
                    <div class="sc-ac-row">
                        <span class="sc-ac-emblem" aria-hidden="true">@include('secretaria.central.icone',['nome'=>'pessoas'])</span>
                        <div><h3>{{ $turma->nome }} · turma lotada</h3>
                            <p>{{ $turma->ocupacao_ativa }} de {{ $turma->capacidade }} vagas ocupadas</p></div>
                        <a class="sc-button sc-button-small" href="{{ route('secretaria.matriculas.turma',$turma) }}">Ver turma →</a>
                    </div>
                @endforeach
                @foreach ($trancadas as $matricula)
                    <div class="sc-ac-row">
                        <span class="sc-ac-emblem" aria-hidden="true">@include('secretaria.central.icone',['nome'=>'relogio'])</span>
                        <div><h3>{{ $matricula->user?->name ?? 'Aluno não identificado' }} · matrícula trancada</h3>
                            <p>{{ $matricula->curso?->titulo ?? 'Curso não informado' }} · Acompanhar situação acadêmica</p></div>
                        <a class="sc-button sc-button-small" href="{{ route('secretaria.matriculas.show',$matricula) }}">Ver ficha →</a>
                    </div>
                @endforeach
                <div class="sc-panel-note">
                    <a class="sc-ac-footer-link" href="{{ route('secretaria.matriculas.index', ['turma'=>'sem','situacao'=>'ativas']) }}">Consultar todas as matrículas sem turma →</a>
                </div>
            </section>
            <section class="sc-panel">
                <div class="sc-panel-heading"><h2>Fluxo acadêmico organizado</h2><p>Inscrições e acesso permanecem na Gestão de Acessos.</p></div>
                <div class="sc-panel-body">
                    <p class="sc-ac-muted">Após a inscrição e a aprovação do candidato, a Secretaria organiza a matrícula no curso e seus vínculos com turmas. Cada curso possui sua própria matrícula acadêmica.</p>
                    <div class="sc-ac-mini-flow"><span>Aluno cadastrado</span><b>→</b><span>Matrícula no curso</span><b>→</b><span>Turma / período</span><b>→</b><span>Histórico</span></div>
                </div>
            </section>
            <section class="sc-panel">
                <div class="sc-panel-heading"><h2>Últimas movimentações</h2><p>Operações registradas nesta Central.</p></div>
                @forelse ($ultimas as $evento)
                    <div class="sc-ac-row">
                        <span class="sc-ac-emblem" aria-hidden="true">@include('secretaria.central.icone',['nome'=>'documento'])</span>
                        <div><h3>{{ $evento->aluno ?? 'Aluno não identificado' }} · {{ str_replace('_',' ',$evento->tipo) }}</h3>
                            <p>{{ $evento->numero_matricula ?: 'Sem número' }} · {{ \Illuminate\Support\Carbon::parse($evento->created_at)->format('d/m/Y H:i') }}</p></div>
                    </div>
                @empty
                    <div class="sc-panel-body"><p class="sc-ac-muted">Nenhuma movimentação registrada por este módulo.</p></div>
                @endforelse
                <div class="sc-panel-note"><a class="sc-ac-footer-link" href="{{ route('secretaria.matriculas.movimentacoes') }}">Ver histórico completo →</a></div>
            </section>
        </div>
        <aside class="sc-aside" aria-label="Orientações para a Gestão Acadêmica">
            <section class="sc-side-card sc-side-gold">
                <h2>Critérios importantes</h2>
                <p>O número da matrícula acadêmica pertence ao curso e <strong>não altera o identificador de login</strong>.</p>
                <ul>
                    <li>Uma pessoa pode cursar duas formações diferentes.</li>
                    <li>Uma matrícula pode ter várias turmas de disciplinas distintas.</li>
                    <li>Movimentações precisam preservar frequência e histórico.</li>
                    <li>Ações sensíveis exigem motivo e senha administrativa.</li>
                </ul>
            </section>
            <section class="sc-side-card"><h2>Atalhos operacionais</h2>
                <div class="sc-ac-quick">
                    <a class="sc-button" href="{{ route('secretaria.matriculas.index') }}">Pesquisar matrícula →</a>
                    <a class="sc-button" href="{{ route('secretaria.matriculas.turmas') }}">Consultar turmas →</a>
                    <a class="sc-button" href="{{ route('secretaria.matriculas.movimentacoes') }}">Ver movimentações →</a>
                </div>
            </section>
            <section class="sc-side-card"><h2>O que fica fora daqui</h2><p>Inscrições, ativação de contas e recuperação de senha permanecem na Gestão de Acessos.</p><a class="sc-text-link" href="{{ route('secretaria.acessos.index') }}">Abrir Gestão de Acessos</a></section>
        </aside>
    </div>
@endsection
