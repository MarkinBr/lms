@extends('secretaria.central.layout')
@section('title', 'Ficha da turma')
@section('breadcrumb')<span aria-hidden="true">/</span><a href="{{ route('secretaria.matriculas.index') }}">Matrículas e turmas</a><span aria-hidden="true">/</span><a href="{{ route('secretaria.matriculas.turmas') }}">Turmas</a><span aria-hidden="true">/</span><span aria-current="page">Ficha da turma</span>@endsection
@section('content')
    <a class="sc-back" href="{{ route('secretaria.matriculas.turmas') }}">@include('secretaria.central.icone', ['nome' => 'voltar']) Voltar às turmas</a>
    <div class="sc-intro">
        <div><p class="sc-eyebrow">Organização acadêmica</p><h1>{{ $turma->nome }}</h1><p>{{ $turma->curso?->titulo ?? 'Curso não informado' }} · {{ $turma->ano_letivo ?? 'Ano não informado' }}</p></div>
        <a class="sc-button sc-primary" href="{{ route('filament.secretario.resources.turma-secretarias.edit', $turma) }}">Editar turma @include('secretaria.central.icone', ['nome' => 'seta'])</a>
    </div>
    @include('secretaria.central.matriculas.nav', ['secao' => 'turmas'])
    <section class="sc-panel">
        <div class="sc-panel-heading"><h2>Dados da turma</h2></div>
        <dl class="sc-data-grid">
            <div><dt>Nome</dt><dd>{{ $turma->nome }}</dd></div>
            <div><dt>Código</dt><dd>{{ $turma->codigo ?: 'Não informado' }}</dd></div>
            <div><dt>Curso</dt><dd>{{ $turma->curso?->titulo ?? 'Não informado' }}</dd></div>
            <div><dt>Período letivo</dt><dd>{{ $turma->periodoLetivo?->nome ?? 'Não informado' }}</dd></div>
            <div><dt>Professor</dt><dd>{{ $turma->professor?->name ?? 'Não atribuído' }}</dd></div>
            <div><dt>Situação</dt><dd>{{ \App\Models\Turma::STATUS_LABELS[$turma->status] ?? 'Não informada' }}</dd></div>
            <div><dt>Modalidade</dt><dd>{{ \App\Models\Turma::MODALIDADE_LABELS[$turma->modalidade] ?? 'Não informada' }}</dd></div>
            <div><dt>Capacidade</dt><dd>{{ $turma->capacidade ?? 'Não definida' }}</dd></div>
        </dl>
    </section>
    <section class="sc-panel">
        <div class="sc-panel-heading"><h2>Matrículas associadas</h2><p>Inclui vínculos atuais e registros históricos de turma; uma mesma matrícula aparece uma vez.</p></div>
        <div class="sc-list-label"><span>{{ $matriculas->total() }} matrículas encontradas</span></div>
        <ul class="sc-students">
            @forelse($matriculas as $matricula)
                <li class="sc-student">
                    <div class="sc-person"><span class="sc-avatar" aria-hidden="true">@include('secretaria.central.icone', ['nome' => 'pessoas'])</span>
                        <div><h3>{{ $matricula->user?->name ?? 'Aluno não vinculado' }}</h3>
                        <p>Nº {{ $matricula->numero_matricula ?: 'Não informado' }} · {{ ucfirst($matricula->status ?? 'Sem situação') }}</p></div>
                    </div>
                    <a class="sc-button" href="{{ route('secretaria.matriculas.show', $matricula) }}">Ver matrícula</a>
                </li>
            @empty
                <li class="sc-empty"><h3>Turma sem matrículas registradas</h3><p>Vincule uma matrícula na ficha acadêmica do aluno.</p></li>
            @endforelse
        </ul>
        <div class="sc-panel-body">{{ $matriculas->links() }}</div>
    </section>
@endsection
