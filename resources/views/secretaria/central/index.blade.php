@extends('secretaria.central.layout')
@section('title', 'Acessos dos alunos')
@section('breadcrumb')<span aria-hidden="true">/</span><span aria-current="page">Acessos dos alunos</span>@endsection
@section('content')
    <div class="sc-intro">
        <div><p class="sc-eyebrow">Cuidar do acesso, aproximar o aluno</p><h1>Acessos dos alunos</h1><p>Encontre o aluno e abra a ficha para atender com segurança.</p></div>
        <a class="sc-button sc-primary" href="{{ route('secretaria.acessos.create') }}">@include('secretaria.central.icone', ['nome' => 'mais']) Cadastrar novo acesso</a>
    </div>
    <div class="sc-layout">
        <section class="sc-panel" aria-labelledby="lista-titulo">
            <div class="sc-panel-heading"><h2 id="lista-titulo">Alunos cadastrados</h2><p>Busque por nome, matrícula de acesso, CPF ou e-mail.</p>
                <form action="{{ route('secretaria.acessos.index') }}" method="get" role="search" class="sc-search-form">
                    <label class="sc-sr-only" for="busca">Buscar aluno por nome, matrícula, CPF ou e-mail</label>
                    <div class="sc-search">@include('secretaria.central.icone', ['nome' => 'busca'])<input id="busca" name="busca" type="search" value="{{ $busca }}" placeholder="Nome, matrícula, CPF ou e-mail" maxlength="180"></div>
                    <label class="sc-sr-only" for="filtro">Filtrar situação do acesso</label>
                    <select id="filtro" name="filtro">
                        @foreach (['todos' => 'Todos os alunos', 'ativos' => 'Contas ativas', 'bloqueados' => 'Contas bloqueadas', 'pendentes' => 'Primeiro acesso pendente'] as $valor => $rotulo)
                            <option value="{{ $valor }}" @selected($filtro === $valor)>{{ $rotulo }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="sc-button sc-primary">Buscar</button>
                </form>
                @if ($busca !== '' || ($filtro && $filtro !== 'todos'))<a class="sc-text-link" href="{{ route('secretaria.acessos.index') }}">Limpar busca e filtros</a>@endif
            </div>
            <div class="sc-list-label"><span>{{ number_format($alunos->total(), 0, ',', '.') }} {{ $alunos->total() === 1 ? 'aluno encontrado' : 'alunos encontrados' }}</span><span>Cadastro e acesso</span></div>
            <ul class="sc-students">
                @forelse ($alunos as $aluno)
                    <li class="sc-student">
                        <div class="sc-person">@include('secretaria.central.avatar', ['grande' => false])<div><h3>{{ $aluno->name }}</h3><p>Matrícula de acesso · {{ $aluno->matricula_login ?: 'Não informada' }}</p><p>{{ $aluno->email ?: 'E-mail não cadastrado' }}</p></div></div>
                        <div class="sc-student-actions">
                            <a class="sc-button" href="{{ route('secretaria.acessos.formas', $aluno) }}">Ver formas de acesso <span class="sc-sr-only">de {{ $aluno->name }}</span></a>
                            <a class="sc-button" href="{{ route('secretaria.acessos.show', $aluno) }}">Abrir ficha <span class="sc-sr-only">de {{ $aluno->name }}</span>@include('secretaria.central.icone', ['nome' => 'seta'])</a>
                        </div>
                        @include('secretaria.central.status')
                    </li>
                @empty
                    <li class="sc-empty">@include('secretaria.central.icone', ['nome' => 'busca'])<h3>Nenhum aluno encontrado</h3><p>Confira a busca ou altere o filtro. Você também pode cadastrar um novo acesso.</p></li>
                @endforelse
            </ul>
            @if ($alunos->hasPages())
                <nav class="sc-pagination" aria-label="Paginação dos alunos">
                    @if ($alunos->onFirstPage())<span class="sc-button" aria-disabled="true">Anterior</span>@else<a class="sc-button" rel="prev" href="{{ $alunos->appends(['busca' => $busca, 'filtro' => $filtro])->previousPageUrl() }}">Anterior</a>@endif
                    <span>Página {{ $alunos->currentPage() }} de {{ $alunos->lastPage() }}</span>
                    @if ($alunos->hasMorePages())<a class="sc-button" rel="next" href="{{ $alunos->appends(['busca' => $busca, 'filtro' => $filtro])->nextPageUrl() }}">Próxima</a>@else<span class="sc-button" aria-disabled="true">Próxima</span>@endif
                </nav>
            @endif
        </section>
        <aside class="sc-aside" aria-label="Orientações de atendimento">
            <section class="sc-side-card sc-side-gold">@include('secretaria.central.icone', ['nome' => 'chave'])<h2>Um acesso, um aluno.</h2><p>Atenda a conta certa: confira a identidade e o contato do próprio aluno antes de preparar qualquer envio.</p></section>
            @if (!empty($totais))
                <section class="sc-side-card"><h2>Visão dos acessos</h2><dl class="sc-summary">
                    @foreach (['todos' => 'Alunos cadastrados', 'ativos' => 'Contas ativas', 'bloqueados' => 'Contas bloqueadas', 'primeiro_acesso' => 'Primeiro acesso pendente'] as $chave => $rotulo)
                        @if (isset($totais[$chave]))<div><dt>{{ $rotulo }}</dt><dd>{{ number_format($totais[$chave], 0, ',', '.') }}</dd></div>@endif
                    @endforeach
                </dl></section>
            @endif
            <section class="sc-side-card"><h2>Atendimento com segurança</h2><ul><li>Conta ativa e e-mail confirmado são situações diferentes.</li><li>WhatsApp abre um rascunho para envio manual.</li><li>Cadastrar acesso não cria matrícula acadêmica.</li></ul></section>
        </aside>
    </div>
@endsection
