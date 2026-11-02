@extends('secretaria.central.layout')
@section('title','Quiz, provas, avaliações e simulados')
@section('breadcrumb')<span aria-hidden="true">/</span><a href="{{ route('secretaria.ensino.painel') }}">Ensino e aprendizagem</a><span aria-hidden="true">/</span><span>Quiz, provas e avaliações</span>@endsection
@section('content')
<div class="sc-intro"><div><p class="sc-eyebrow">Instrumentos de aprendizagem</p><h1>Quiz, provas, avaliações e simulados</h1><p>Quizzes pertencem às aulas; provas, avaliações e simulados são independentes e vinculados à disciplina.</p></div>
<a class="sc-button" href="{{ route('secretaria.ensino.questoes') }}">Banco de questões →</a></div>
@include('secretaria.central.ensino.nav',['secao'=>'avaliacoes'])
<div class="sc-en-grid" style="margin-bottom:23px">
@foreach(['quiz'=>'Quizzes das aulas','prova'=>'Provas','avaliacao'=>'Avaliações','simulado'=>'Simulados'] as $chave=>$titulo)
<a class="sc-en-tile" href="{{ route('secretaria.ensino.avaliacoes',['tipo'=>$chave]) }}" style="min-height:126px">
<h3>{{ $titulo }}</h3><p>{{ $totais[$chave]??0 }} instrumentos cadastrados</p><strong>{{ $tipo===$chave?'Exibindo agora':'Filtrar →' }}</strong></a>
@endforeach
</div>
<div class="sc-en-two"><section class="sc-panel"><div class="sc-panel-heading">
<h2>{{ ['quiz'=>'Quizzes vinculados às aulas','prova'=>'Provas das disciplinas','avaliacao'=>'Avaliações independentes','simulado'=>'Simulados de prática'][$tipo]??'Todos os instrumentos' }}</h2>
<p>Itens do banco são reutilizáveis. Cada instrumento é criado como rascunho para revisão.</p>
@if($tipo!=='')<a class="sc-text-link" href="{{ route('secretaria.ensino.avaliacoes') }}">Ver todos</a>@endif
</div>
<ul class="sc-en-list">
@forelse($instrumentos as $instrumento)<li><div>
<h3>{{ $instrumento->titulo }} <span class="sc-en-badge {{ $instrumento->status==='rascunho'?'warn':'' }}">{{ ucfirst($instrumento->status) }}</span></h3>
<p>{{ ucfirst($instrumento->tipo) }} · {{ $instrumento->disciplina?->nome }}{{ $instrumento->aula ? ' · Aula: '.$instrumento->aula->titulo : '' }}</p>
<p>{{ $instrumento->questoes_count }} questões · {{ $instrumento->tempo_limite_minutos ? $instrumento->tempo_limite_minutos.' minutos' : 'Sem limite de tempo' }}</p>
</div><a class="sc-button sc-button-small" href="{{ route('secretaria.ensino.instrumento',$instrumento) }}">Configurar →</a></li>
@empty<li>Nenhum instrumento encontrado.</li>@endforelse
</ul><div class="sc-panel-body">{{ $instrumentos->links() }}</div></section>
<aside class="sc-aside"><section class="sc-side-card sc-side-gold"><h2>Criar instrumento</h2>
<form method="post" class="sc-en-form" action="{{ route('secretaria.ensino.avaliacoes.salvar') }}">@csrf
<div><label for="in-tipo">Tipo</label><select id="in-tipo" name="tipo" required>
@foreach(['quiz'=>'Quiz dentro da aula','prova'=>'Prova independente','avaliacao'=>'Avaliação independente','simulado'=>'Simulado de estudo'] as $k=>$label)<option value="{{ $k }}" @selected(old('tipo',$tipo?:'quiz')===$k)>{{ $label }}</option>@endforeach
</select></div>
<div><label for="in-titulo">Título</label><input id="in-titulo" type="text" name="titulo" maxlength="255" required placeholder="Ex.: Revisão de História da Igreja" value="{{ old('titulo') }}"></div>
<div><label for="in-disc">Disciplina</label><select id="in-disc" name="disciplina_id" required><option value="">Selecione</option>@foreach($disciplinas as $disc)<option value="{{ $disc->id }}" @selected(old('disciplina_id')==$disc->id)>{{ $disc->nome }}</option>@endforeach</select></div>
<div id="campo-aula"><label for="in-aula">Aula do quiz</label><select id="in-aula" name="aula_id"><option value="">Selecione a aula</option>@foreach($aulas as $aula)<option value="{{ $aula->id }}" @selected(old('aula_id')==$aula->id)>{{ $aula->titulo }}</option>@endforeach</select>
<small>Obrigatória somente para quiz.</small></div>
<div><label for="in-instrucoes">Instruções (opcional)</label><textarea id="in-instrucoes" name="instrucoes" rows="3">{{ old('instrucoes') }}</textarea></div>
<div class="sc-en-fields"><div><label for="in-tempo">Tempo (min)</label><input id="in-tempo" type="number" name="tempo_limite_minutos" min="1" max="600" value="{{ old('tempo_limite_minutos') }}"></div>
<div><label for="in-tentativas">Tentativas</label><input id="in-tentativas" type="number" name="tentativas_permitidas" required min="1" max="10" value="{{ old('tentativas_permitidas',1) }}"></div></div>
<label class="sc-en-check"><input type="checkbox" name="embaralhar" value="1" @checked(old('embaralhar'))> Embaralhar as questões</label>
<button type="submit" class="sc-button sc-primary">Criar rascunho</button></form>
</section><section class="sc-side-card"><h2>Como ficam separados?</h2><p>Quiz → vinculado a uma aula.</p><p>Prova e avaliação → vinculadas à disciplina, sem aula obrigatória.</p><p>Simulado → prática independente, sem lançamento automático no boletim.</p></section></aside></div>
<script>
document.addEventListener('DOMContentLoaded',()=>{
const tipo=document.querySelector('#in-tipo'),campo=document.querySelector('#campo-aula'),aula=document.querySelector('#in-aula');
if(!tipo||!campo||!aula)return;
function ajustar(){const quiz=tipo.value==='quiz';campo.hidden=!quiz;aula.disabled=!quiz;aula.required=quiz;if(!quiz)aula.value='';}
tipo.addEventListener('change',ajustar);ajustar();
});
</script>
@endsection
