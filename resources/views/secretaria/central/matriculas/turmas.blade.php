@extends('secretaria.central.layout')
@section('title', 'Gestão de Turmas')
@section('breadcrumb')<span aria-hidden="true">/</span><a href="{{ route('secretaria.matriculas.index') }}">Matrículas e turmas</a><span aria-hidden="true">/</span><span aria-current="page">Turmas</span>@endsection
@section('content')
    <div class="sc-intro">
        <div><p class="sc-eyebrow">Organização acadêmica</p><h1>Turmas</h1><p>Consulte os grupos, períodos, professores e matrículas vinculadas.</p></div>
        <a class="sc-button sc-primary" href="{{ route('filament.secretario.resources.turma-secretarias.create') }}">@include('secretaria.central.icone', ['nome' => 'mais']) Nova turma</a>
    </div>
    @include('secretaria.central.matriculas.nav', ['secao' => 'turmas'])
    <section class="sc-panel" aria-labelledby="turmas-lista">
        <div class="sc-panel-heading"><h2 id="turmas-lista">Turmas cadastradas</h2>
            <form class="sc-search-form" method="get" action="{{ route('secretaria.matriculas.turmas') }}" role="search">
                <label for="turmas-busca" class="sc-sr-only">Nome ou código da turma</label>
                <div class="sc-search">@include('secretaria.central.icone', ['nome' => 'busca'])<input id="turmas-busca" name="busca" value="{{ $busca }}" type="search" maxlength="150" placeholder="Nome ou código"></div>
                <label for="turmas-curso" class="sc-sr-only">Filtrar curso</label>
                <select name="curso" id="turmas-curso"><option value="">Todos os cursos</option>
                    @foreach($cursos as $curso)<option value="{{ $curso->id }}" @selected($cursoId === $curso->id)>{{ $curso->titulo }}</option>@endforeach
                </select>
                <button type="submit" class="sc-button sc-primary">Buscar</button>
            </form>
        </div>
        <div class="sc-list-label"><span>{{ number_format($turmas->total(), 0, ',', '.') }} turmas encontradas</span><span>Organização das turmas</span></div>
        <ul class="sc-students">
            @forelse($turmas as $turma)
                <li class="sc-student">
                    <div class="sc-person">
                        <span class="sc-avatar" aria-hidden="true">@include('secretaria.central.icone', ['nome' => 'pessoas'])</span>
                        <div><h3>{{ $turma->nome }}</h3>
                            <p>{{ $turma->curso?->titulo ?? 'Curso não informado' }} · {{ $turma->ano_letivo ?? 'Ano não informado' }} · {{ \App\Models\Turma::STATUS_LABELS[$turma->status] ?? 'Sem situação' }}</p>
                            <p>Professor: {{ $turma->professor?->name ?? 'Não atribuído' }} · {{ $turma->legadas_count }} vínculos legados / {{ $turma->vinculos_count }} registros complementares</p>
                        </div>
                    </div>
                    <a class="sc-button" href="{{ route('secretaria.matriculas.turma', $turma) }}">Ver turma @include('secretaria.central.icone', ['nome' => 'seta'])</a>
                </li>
            @empty
                <li class="sc-empty"><h3>Nenhuma turma encontrada</h3><p>Cadastre uma turma pelo painel acadêmico ou ajuste a pesquisa.</p></li>
            @endforelse
        </ul>
        <div class="sc-panel-body">{{ $turmas->links() }}</div>
    </section>
@endsection
