@extends('secretaria.central.layout')
@section('title', 'Gestão de Matrículas')
@section('breadcrumb')<span aria-hidden="true">/</span><span aria-current="page">Matrículas e turmas</span>@endsection
@section('content')
    <div class="sc-intro">
        <div><p class="sc-eyebrow">Gestão acadêmica</p><h1>Matrículas e turmas</h1><p>Pesquise matrículas, consulte vínculos e acompanhe a organização acadêmica.</p></div>
        <a class="sc-button sc-primary" href="{{ route('filament.secretario.resources.matricula-secretarias.create') }}">@include('secretaria.central.icone', ['nome' => 'mais']) Nova matrícula</a>
    </div>
    @include('secretaria.central.matriculas.nav', ['secao' => 'matriculas'])
    <div class="sc-layout">
        <section class="sc-panel" aria-labelledby="lista-matriculas">
            <div class="sc-panel-heading">
                <h2 id="lista-matriculas">Matrículas cadastradas</h2>
                <p>A matrícula acadêmica é diferente da matrícula utilizada no login.</p>
                <form method="get" action="{{ route('secretaria.matriculas.index') }}" role="search" class="sc-search-form">
                    <label for="busca-matricula" class="sc-sr-only">Pesquisar matrícula por aluno ou número</label>
                    <div class="sc-search">@include('secretaria.central.icone', ['nome' => 'busca'])<input id="busca-matricula" name="busca" type="search" maxlength="150" value="{{ $busca }}" placeholder="Nome do aluno ou número"></div>
                    <label for="curso-matricula" class="sc-sr-only">Filtrar por curso</label>
                    <select name="curso" id="curso-matricula"><option value="">Todos os cursos</option>
                        @foreach ($cursos as $curso)<option value="{{ $curso->id }}" @selected($cursoId === $curso->id)>{{ $curso->titulo }}</option>@endforeach
                    </select>
                    <label for="situacao-matricula" class="sc-sr-only">Filtrar situação</label>
                    <select name="situacao" id="situacao-matricula">
                        @foreach (['' => 'Todas as situações', 'ativas' => 'Ativas', 'trancadas' => 'Trancadas', 'concluidas' => 'Concluídas', 'canceladas' => 'Canceladas'] as $valor => $rotulo)
                            <option value="{{ $valor }}" @selected($situacao === $valor)>{{ $rotulo }}</option>
                        @endforeach
                    </select>
                    <button class="sc-button sc-primary" type="submit">Buscar</button>
                </form>
                @if ($busca !== '' || $cursoId || $situacao !== '')<a class="sc-text-link" href="{{ route('secretaria.matriculas.index') }}">Limpar filtros</a>@endif
            </div>
            <div class="sc-list-label"><span>{{ number_format($matriculas->total(), 0, ',', '.') }} matrículas encontradas</span><span>Vínculo acadêmico</span></div>
            <ul class="sc-students">
                @forelse ($matriculas as $matricula)
                    <li class="sc-student">
                        <div class="sc-person">
                            <span class="sc-avatar" aria-hidden="true">@include('secretaria.central.icone', ['nome' => 'pessoas'])</span>
                            <div>
                                <h3>{{ $matricula->user?->name ?? 'Aluno não vinculado' }}</h3>
                                <p><strong>Nº {{ $matricula->numero_matricula ?: 'Não informado' }}</strong> · {{ $matricula->curso?->titulo ?? 'Curso não informado' }}</p>
                                <p>{{ $matricula->turma?->nome ?? 'Turma legada não vinculada' }} · {{ ucfirst($matricula->status ?? 'Sem situação') }}</p>
                            </div>
                        </div>
                        <a class="sc-button" href="{{ route('secretaria.matriculas.show', $matricula) }}">Ver matrícula @include('secretaria.central.icone', ['nome' => 'seta'])</a>
                    </li>
                @empty
                    <li class="sc-empty"><h3>Nenhuma matrícula encontrada</h3><p>Altere os filtros ou cadastre uma matrícula pelo painel acadêmico.</p></li>
                @endforelse
            </ul>
            <div class="sc-panel-body">{{ $matriculas->links() }}</div>
        </section>
        <aside class="sc-aside" aria-label="Indicadores e orientações">
            <section class="sc-side-card"><h2>Resumo acadêmico</h2><dl class="sc-summary">
                <div><dt>Matrículas cadastradas</dt><dd>{{ number_format($totais['matriculas'], 0, ',', '.') }}</dd></div>
                <div><dt>Matrículas ativas</dt><dd>{{ number_format($totais['ativas'], 0, ',', '.') }}</dd></div>
                <div><dt>Sem turma ativa identificada</dt><dd>{{ number_format($totais['sem_turma'], 0, ',', '.') }}</dd></div>
                <div><dt>Turmas cadastradas</dt><dd>{{ number_format($totais['turmas'], 0, ',', '.') }}</dd></div>
            </dl></section>
            <section class="sc-side-card sc-side-gold"><h2>Inscrições e acessos</h2><p>A análise de inscrições e o primeiro acesso ficam na Gestão de Acessos. Este módulo administra os vínculos acadêmicos.</p><a class="sc-text-link" href="{{ route('secretaria.acessos.index') }}">Abrir Gestão de Acessos</a></section>
        </aside>
    </div>
@endsection
