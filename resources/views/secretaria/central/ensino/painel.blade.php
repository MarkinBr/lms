@extends('secretaria.central.layout')
@section('title','Ensino e aprendizagem')
@section('breadcrumb')<span aria-hidden="true">/</span><span aria-current="page">Ensino e aprendizagem</span>@endsection
@section('content')
<div class="sc-intro"><div><p class="sc-eyebrow">Gestão pedagógica</p><h1>Ensino e aprendizagem</h1>
<p>Organize a matriz curricular, prepare a sala de aula e acompanhe avaliações e as duas formas de frequência.</p></div>
<a class="sc-button sc-primary" href="{{ route('secretaria.ensino.aulas.nova') }}">@include('secretaria.central.icone',['nome'=>'mais']) Preparar aula</a></div>
@include('secretaria.central.ensino.nav',['secao'=>'painel'])
<dl class="sc-en-metrics">
<div class="sc-en-metric"><dt>Módulos</dt><dd>{{ $totais['modulos'] }}</dd><a href="{{ route('secretaria.ensino.modulos') }}">Organizar cursos</a></div>
<div class="sc-en-metric"><dt>Disciplinas</dt><dd>{{ $totais['disciplinas'] }}</dd><a href="{{ route('secretaria.ensino.disciplinas') }}">Matriz curricular</a></div>
<div class="sc-en-metric"><dt>Aulas cadastradas</dt><dd>{{ $totais['aulas'] }}</dd><a href="{{ route('secretaria.ensino.aulas') }}">Gerenciar conteúdos</a></div>
<div class="sc-en-metric"><dt>Chamadas em rascunho</dt><dd>{{ $totais['chamadas_pendentes'] }}</dd><a href="{{ route('secretaria.ensino.frequencias') }}">Acompanhar chamadas</a></div>
</dl>
<div class="sc-en-grid" style="margin-bottom:23px">
<a class="sc-en-tile" href="{{ route('secretaria.ensino.modulos') }}"><span class="sc-icon-box">@include('secretaria.central.icone',['nome'=>'livro'])</span><h3>Módulos e disciplinas</h3><p>Curso → módulo → disciplina → aula. Organize carga horária e sequência curricular.</p><strong>Abrir organização →</strong></a>
<a class="sc-en-tile" href="{{ route('secretaria.ensino.aulas') }}"><span class="sc-icon-box">@include('secretaria.central.icone',['nome'=>'documento'])</span><h3>Aulas e materiais</h3><p>Links de vídeos, textos, transcrições e arquivos para a sala de aula.</p><strong>Preparar aulas →</strong></a>
<a class="sc-en-tile" href="{{ route('secretaria.ensino.avaliacoes') }}"><span class="sc-icon-box">@include('secretaria.central.icone',['nome'=>'editar'])</span><h3>Quiz, provas e simulados</h3><p>Quiz dentro da aula. Provas, avaliações e simulados separados, com banco reutilizável.</p><strong>Gerenciar avaliações →</strong></a>
<a class="sc-en-tile" href="{{ route('secretaria.ensino.notas') }}"><span class="sc-icon-box">@include('secretaria.central.icone',['nome'=>'documento'])</span><h3>Notas e resultados</h3><p>Consulte os registros de notas e médias existentes do sistema.</p><strong>Consultar notas →</strong></a>
<a class="sc-en-tile" href="{{ route('secretaria.ensino.frequencias') }}"><span class="sc-icon-box">@include('secretaria.central.icone',['nome'=>'agenda'])</span><h3>Frequência presencial</h3><p>Chamadas oficiais concluídas, presenças, faltas e justificativas.</p><strong>Consultar chamadas →</strong></a>
<a class="sc-en-tile" href="{{ route('secretaria.ensino.ead') }}"><span class="sc-icon-box">@include('secretaria.central.icone',['nome'=>'relogio'])</span><h3>Participação EAD</h3><p>Acompanhamento da atividade digital separado da frequência presencial oficial.</p><strong>Consultar progresso →</strong></a>
</div>
<div class="sc-en-two">
<section class="sc-panel"><div class="sc-panel-heading"><h2>Pendências pedagógicas</h2><p>Aulas ainda não liberadas e chamadas que precisam ser concluídas.</p></div>
@forelse($aulasPendentes as $aula)
<div class="sc-en-stat"><div><strong>{{ $aula->titulo }}</strong><p>{{ $aula->disciplina?->nome ?? 'Disciplina legada' }} · aula não liberada</p></div><a class="sc-button sc-button-small" href="{{ route('secretaria.ensino.aula',$aula) }}">Revisar →</a></div>
@empty<div class="sc-panel-body"><p class="sc-en-note">Nenhuma aula não liberada identificada.</p></div>@endforelse
@foreach($chamadasPendentes as $chamada)<div class="sc-en-stat"><div><strong>{{ $chamada->turma?->nome ?? 'Turma' }}</strong><p>{{ $chamada->disciplina?->nome ?? 'Disciplina' }} · chamada em rascunho</p></div><a class="sc-button sc-button-small" href="{{ route('secretaria.ensino.frequencias') }}">Ver →</a></div>@endforeach
</section>
<aside class="sc-aside"><section class="sc-side-card sc-side-gold"><h2>Regras pedagógicas</h2><p>O tempo registrado e a conclusão técnica de uma videoaula <strong>não comprovam atenção</strong>. A participação EAD não será convertida automaticamente em presença oficial.</p></section>
<section class="sc-side-card"><h2>Questões em revisão</h2><p>{{ $totais['questoes_rascunho'] }} questão(ões) aguardando revisão humana.</p><a class="sc-text-link" href="{{ route('secretaria.ensino.questoes') }}">Abrir banco de questões</a></section></aside>
</div>
@endsection
