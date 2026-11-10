@extends('secretaria.central.layout')
@section('title','Disciplinas')
@section('breadcrumb')<span aria-hidden="true">/</span><a href="{{ route('secretaria.ensino.painel') }}">Ensino e aprendizagem</a><span aria-hidden="true">/</span><span>Disciplinas</span>@endsection
@section('content')
<div class="sc-intro"><div><p class="sc-eyebrow">Matriz curricular</p><h1>Gestão de disciplinas</h1><p>Acompanhe disciplinas, módulos, ordem curricular, carga horária e aulas vinculadas.</p></div>
<a class="sc-button sc-primary" href="{{ route('filament.secretario.resources.disciplina-secretarias.index') }}">@include('secretaria.central.icone',['nome'=>'mais']) Gerenciar disciplinas</a></div>
@include('secretaria.central.ensino.nav',['secao'=>'disciplinas'])
<section class="sc-panel"><div class="sc-panel-heading"><h2>Disciplinas cadastradas</h2>
<form method="get" action="{{ route('secretaria.ensino.disciplinas') }}" class="sc-en-filters">
<input type="search" name="busca" value="{{ $f['busca'] }}" aria-label="Pesquisar disciplina" placeholder="Buscar por disciplina">
<select name="curso" aria-label="Filtrar curso"><option value="">Todos os cursos</option>@foreach($cursos as $curso)<option value="{{ $curso->id }}" @selected($f['curso']===$curso->id)>{{ $curso->titulo }}</option>@endforeach</select>
<button class="sc-button sc-primary">Buscar</button></form></div>
<ul class="sc-en-list">@forelse($disciplinasLista as $disciplina)
<li><div><h3>{{ $disciplina->nome }} <span class="sc-en-badge neutral">{{ $disciplina->carga_horaria }}h</span></h3>
<p>{{ $disciplina->curso?->titulo ?? 'Curso não informado' }} → {{ $disciplina->modulo?->titulo ?? 'Módulo pendente' }} · {{ $disciplina->aulas_count }} aulas</p>
<p>{{ $disciplina->codigo }} · {{ $disciplina->ativa ? 'Ativa' : 'Inativa' }}</p></div>
<a class="sc-button sc-button-small" href="{{ route('secretaria.ensino.aulas',['disciplina'=>$disciplina->id]) }}">Ver aulas →</a></li>
@empty<li>Nenhuma disciplina encontrada.</li>@endforelse</ul>
<div class="sc-panel-body">{{ $disciplinasLista->links() }}</div></section>
<p class="sc-support-note">O cadastro e a edição completa das disciplinas usam o formulário acadêmico existente, com validação do vínculo curso/módulo.</p>
@endsection
