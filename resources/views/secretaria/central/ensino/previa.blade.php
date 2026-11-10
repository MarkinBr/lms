@extends('secretaria.central.layout')
@section('title','Prévia do instrumento')
@section('breadcrumb')<span aria-hidden="true">/</span><a href="{{ route('secretaria.ensino.painel') }}">Ensino e aprendizagem</a><span aria-hidden="true">/</span><a href="{{ route('secretaria.ensino.avaliacoes') }}">Avaliações</a><span aria-hidden="true">/</span><span>Prévia</span>@endsection
@section('content')
<a class="sc-back" href="{{ route('secretaria.ensino.instrumento',$instrumento) }}">@include('secretaria.central.icone',['nome'=>'voltar']) Voltar à configuração</a>
<div class="sc-intro"><div><p class="sc-eyebrow">Prévia administrativa</p><h1>{{ $instrumento->titulo }}</h1><p>{{ ucfirst($instrumento->tipo) }} · {{ $instrumento->disciplina?->nome }} · {{ $instrumento->questoes->count() }} questões</p></div></div>
@include('secretaria.central.ensino.nav',['secao'=>'avaliacoes'])
<div class="sc-en-banner">Esta é apenas uma simulação para professores e secretaria. Não grava tentativa, participação, frequência ou nota do aluno. Questões dissertativas exigem correção humana.</div>
@if($resultado)
<section class="sc-panel sc-en-panel"><div class="sc-panel-heading"><h2>Resultado da prévia</h2></div><div class="sc-panel-body">
@if($resultado['percentual']!==null)
<p><strong>{{ $resultado['pontos'] }} de {{ $resultado['maximo'] }} pontos</strong> · {{ $resultado['percentual'] }}% nas questões objetivas</p>
@else<p>Este instrumento possui apenas questões dissertativas, que exigem correção manual.</p>@endif
<p class="sc-en-note">Esse resultado não foi persistido no banco de dados.</p></div></section>
@endif
<form method="post" class="sc-en-blocks" action="{{ route('secretaria.ensino.previa.corrigir',$instrumento) }}">
@csrf
@forelse($instrumento->questoes as $item)
<section class="sc-panel">
<div class="sc-panel-heading"><h2>Questão {{ $item->pivot->ordem }}</h2><p>{{ ucfirst($item->dificuldade) }} · {{ $item->pivot->pontos }} pontos · {{ str_replace('_',' ',$item->tipo) }}</p></div>
<div class="sc-panel-body sc-en-form">
<p><strong>{{ $item->enunciado }}</strong></p>
@if(in_array($item->tipo,['unica','multipla'],true))
@foreach($item->alternativas??[] as $letra=>$alternativa)
<label class="sc-en-choice"><input type="{{ $item->tipo==='multipla'?'checkbox':'radio' }}" name="respostas[{{ $item->id }}][]" value="{{ $letra }}" @checked(in_array($letra,$resultado['questoes'][$item->id]['marcadas']??[],true))>
<strong>{{ $letra }})</strong> {{ $alternativa }}</label>
@endforeach
@elseif($item->tipo==='verdadeiro_falso')
@foreach(['V'=>'Verdadeiro','F'=>'Falso'] as $valor=>$rotulo)
<label class="sc-en-choice"><input type="radio" name="respostas[{{ $item->id }}][]" value="{{ $valor }}" @checked(in_array($valor,$resultado['questoes'][$item->id]['marcadas']??[],true))>{{ $rotulo }}</label>
@endforeach
@else
<div class="sc-en-banner">Resposta dissertativa: exige avaliação humana. Não computada na pontuação automática.</div>
@endif
@if($resultado)
@php $detalhes=$resultado['questoes'][$item->id]??null; @endphp
@if($detalhes)
@if($detalhes['manual'])
<span class="sc-en-badge warn">Correção manual</span>
@else
<span class="sc-en-badge {{ $detalhes['correta']?'':'warn' }}">{{ $detalhes['correta']?'Resposta correta':'Conferir resposta' }}</span>
<p class="sc-en-note">Gabarito: {{ implode(', ',$detalhes['gabarito']) }}</p>
@endif
@if($item->explicacao)<div class="sc-en-source">{{ $item->explicacao }}</div>@endif
@endif
@endif
</div></section>
@empty
<section class="sc-panel"><div class="sc-panel-body">Ainda não há questões vinculadas a este instrumento.</div></section>
@endforelse
@if($instrumento->questoes->isNotEmpty())
<div class="sc-en-inline"><button class="sc-button sc-primary" type="submit">Corrigir respostas desta prévia</button>
<a class="sc-button" href="{{ route('secretaria.ensino.previa',$instrumento) }}">Reiniciar simulação</a></div>
@endif
</form>
@endsection
