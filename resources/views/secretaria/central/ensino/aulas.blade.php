@extends('secretaria.central.layout')
@section('title','Aulas e materiais')
@section('breadcrumb')<span aria-hidden="true">/</span><a href="{{ route('secretaria.ensino.painel') }}">Ensino e aprendizagem</a><span aria-hidden="true">/</span><span>Aulas e materiais</span>@endsection
@section('content')
<div class="sc-intro"><div><p class="sc-eyebrow">Sala de aula</p><h1>Aulas e materiais</h1><p>Prepare videoaulas, textos, transcrições e arquivos em cada disciplina e módulo.</p></div>
<a class="sc-button sc-primary" href="{{ route('secretaria.ensino.aulas.nova') }}">@include('secretaria.central.icone',['nome'=>'mais']) Nova aula</a></div>
@include('secretaria.central.ensino.nav',['secao'=>'aulas'])
<div class="sc-en-two"><section class="sc-panel"><div class="sc-panel-heading"><h2>Conteúdos cadastrados</h2>
<form class="sc-en-filters" action="{{ route('secretaria.ensino.aulas') }}" method="get">
<input aria-label="Buscar aulas" name="busca" type="search" value="{{ $f['busca'] }}" placeholder="Título da aula...">
<select name="curso" aria-label="Curso"><option value="">Todos os cursos</option>@foreach($cursos as $curso)<option value="{{ $curso->id }}" @selected($f['curso']===$curso->id)>{{ $curso->titulo }}</option>@endforeach</select>
<select name="disciplina" aria-label="Disciplina"><option value="">Todas as disciplinas</option>@foreach($disciplinas as $disciplina)<option value="{{ $disciplina->id }}" @selected($f['disciplina']===$disciplina->id)>{{ $disciplina->nome }}</option>@endforeach</select>
<button class="sc-button sc-primary">Filtrar</button></form></div>
<ul class="sc-en-list">@forelse($aulasLista as $aula)<li>
<div><h3>{{ $aula->titulo }} <span class="sc-en-badge {{ $aula->liberada ? '' : 'warn' }}">{{ $aula->liberada ? 'Liberada' : 'Não liberada' }}</span></h3>
<p>{{ $aula->disciplina?->nome ?? 'Disciplina pendente (legado)' }} · {{ $aula->modulo?->titulo ?? 'Módulo não informado' }}</p>
<p>{{ $aula->video_url ? 'Vídeo · ' : '' }}{{ $aula->conteudo_texto ? 'Texto · ' : '' }}{{ count($aula->anexos??[]) }} anexo(s) · Ordem {{ $aula->ordem }}</p></div>
<a class="sc-button sc-button-small" href="{{ route('secretaria.ensino.aula',$aula) }}">Editar aula →</a>
</li>@empty<li>Nenhuma aula localizada.</li>@endforelse</ul>
<div class="sc-panel-body">{{ $aulasLista->links() }}</div></section>
<aside class="sc-aside"><section class="sc-side-card sc-side-gold"><h2>Sala de aula completa</h2><p>Em cada aula, é possível definir título, descrição, texto didático, link de vídeo, transcrição, arquivos, sequência e permissões.</p></section>
<section class="sc-side-card"><h2>Quiz da aula</h2><p>O quiz pertence à aula específica. Provas, avaliações e simulados são instrumentos independentes.</p>
<a class="sc-text-link" href="{{ route('secretaria.ensino.avaliacoes',['tipo'=>'quiz']) }}">Gerenciar quizzes</a></section></aside></div>
@endsection
