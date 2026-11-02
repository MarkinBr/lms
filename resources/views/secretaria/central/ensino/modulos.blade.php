@extends('secretaria.central.layout')
@section('title','Módulos do curso')
@section('breadcrumb')<span aria-hidden="true">/</span><a href="{{ route('secretaria.ensino.painel') }}">Ensino e aprendizagem</a><span aria-hidden="true">/</span><span>Módulos</span>@endsection
@section('content')
<div class="sc-intro"><div><p class="sc-eyebrow">Estrutura acadêmica</p><h1>Gestão de módulos</h1><p>Organize a sequência dos conteúdos de cada curso antes de cadastrar disciplinas e aulas.</p></div></div>
@include('secretaria.central.ensino.nav',['secao'=>'modulos'])
<div class="sc-en-two">
<section class="sc-panel"><div class="sc-panel-heading"><h2>Módulos cadastrados</h2>
<form class="sc-en-filters" method="get" action="{{ route('secretaria.ensino.modulos') }}"><input aria-label="Pesquisar módulo" type="search" name="busca" value="{{ $f['busca'] }}" placeholder="Nome do módulo">
<select aria-label="Curso" name="curso"><option value="">Todos os cursos</option>@foreach($cursos as $curso)<option value="{{ $curso->id }}" @selected($f['curso']===$curso->id)>{{ $curso->titulo }}</option>@endforeach</select><button class="sc-button sc-primary">Filtrar</button></form>
</div><ul class="sc-en-list">@forelse($modulosLista as $modulo)<li><div><h3>{{ $modulo->titulo }} <span class="sc-en-badge neutral">Ordem {{ $modulo->ordem }}</span></h3><p>{{ $modulo->curso?->titulo }} · {{ $modulo->disciplinas_count }} disciplinas</p></div><a class="sc-button sc-button-small" href="{{ route('secretaria.ensino.disciplinas',['curso'=>$modulo->curso_id]) }}">Disciplinas →</a></li>@empty<li>Sem módulos com estes filtros.</li>@endforelse</ul><div class="sc-panel-body">{{ $modulosLista->links() }}</div></section>
<aside class="sc-aside"><section class="sc-side-card sc-side-gold"><h2>Criar módulo</h2>
<form class="sc-en-form" method="post" action="{{ route('secretaria.ensino.modulos.salvar') }}">@csrf
<div><label for="mo-curso">Curso</label><select id="mo-curso" name="curso_id" required><option value="">Selecione</option>@foreach($cursos as $curso)<option value="{{ $curso->id }}" @selected(old('curso_id')==$curso->id)>{{ $curso->titulo }}</option>@endforeach</select></div>
<div><label for="mo-titulo">Nome do módulo</label><input id="mo-titulo" name="titulo" maxlength="200" value="{{ old('titulo') }}" required></div>
<div><label for="mo-ordem">Ordem no curso</label><input id="mo-ordem" name="ordem" type="number" min="1" max="9999" value="{{ old('ordem',1) }}" required></div>
<div><label for="mo-descricao">Descrição (opcional)</label><textarea id="mo-descricao" name="descricao" rows="3">{{ old('descricao') }}</textarea></div>
<button class="sc-button sc-primary" type="submit">Cadastrar módulo</button></form></section>
<section class="sc-side-card"><h2>Sequência curricular</h2><p>Curso → Módulo → Disciplina → Aula. A mesma disciplina não pode mudar livremente de módulo após receber aulas ou turmas.</p></section></aside>
</div>
@endsection
