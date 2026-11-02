@extends('secretaria.central.layout')
@section('title','Configuração do instrumento')
@section('breadcrumb')<span aria-hidden="true">/</span><a href="{{ route('secretaria.ensino.painel') }}">Ensino e aprendizagem</a><span aria-hidden="true">/</span><a href="{{ route('secretaria.ensino.avaliacoes') }}">Avaliações</a><span aria-hidden="true">/</span><span>Configuração</span>@endsection
@section('content')
<a class="sc-back" href="{{ route('secretaria.ensino.avaliacoes') }}">@include('secretaria.central.icone',['nome'=>'voltar']) Voltar às avaliações</a>
<div class="sc-intro"><div><p class="sc-eyebrow">Configuração pedagógica</p><h1>{{ $instrumento->titulo }}</h1>
<p>{{ ucfirst($instrumento->tipo) }} · {{ $instrumento->disciplina?->nome }}{{ $instrumento->aula ? ' · Aula: '.$instrumento->aula->titulo : ' · separado das aulas' }}</p></div>
<span class="sc-en-badge {{ $instrumento->status==='rascunho'?'warn':'' }}">{{ ucfirst($instrumento->status) }}</span></div>
@include('secretaria.central.ensino.nav',['secao'=>'avaliacoes'])
<div class="sc-en-inline" style="margin-bottom:17px">
<a class="sc-button sc-primary" href="{{ route('secretaria.ensino.previa',$instrumento) }}">Resolver prévia do {{ $instrumento->tipo }} →</a>
</div>
<div class="sc-en-banner">
<strong>Separação pedagógica:</strong> este é o editor e a prévia administrativa. A disponibilização das novas provas, quizzes e simulados aos estudantes, o registro de tentativas e o lançamento das notas dependem da integração com o Portal do Aluno. Nenhuma nota é lançada aqui automaticamente.
</div>
<div class="sc-en-two">
<div class="sc-en-blocks">
<section class="sc-panel"><div class="sc-panel-heading"><h2>Questões deste instrumento</h2>
<p>{{ $instrumento->questoes->count() }} questões · {{ $instrumento->tempo_limite_minutos?$instrumento->tempo_limite_minutos.' minutos':'Sem tempo definido' }} · {{ $instrumento->tentativas_permitidas }} tentativa(s)</p></div>
<ul class="sc-en-list">
@forelse($instrumento->questoes as $questao)
<li><div><h3>{{ $questao->pivot->ordem }}. {{ $questao->enunciado }}</h3>
<p>{{ ucfirst($questao->dificuldade) }} · {{ str_replace('_',' ',$questao->tipo) }} · {{ $questao->pivot->pontos }} ponto(s)</p>
@if($questao->alternativas)
@foreach($questao->alternativas as $letra=>$alternativa)<p><strong>{{ $letra }})</strong> {{ $alternativa }}</p>@endforeach
@endif
@if($questao->referencia)<div class="sc-en-source">{{ $questao->referencia }}</div>@endif
</div><span class="sc-en-badge neutral">Revisada</span></li>
@empty<li>Nenhuma questão incluída ainda.</li>@endforelse
</ul></section>
<section class="sc-panel"><div class="sc-panel-heading"><h2>Parâmetros</h2></div>
<dl class="sc-data-grid" style="padding:20px">
<div><dt>Tipo</dt><dd>{{ ucfirst($instrumento->tipo) }}</dd></div>
<div><dt>Disciplina</dt><dd>{{ $instrumento->disciplina?->nome }}</dd></div>
<div><dt>Aula</dt><dd>{{ $instrumento->aula?->titulo??'Não se aplica' }}</dd></div>
<div><dt>Embaralhamento</dt><dd>{{ $instrumento->embaralhar?'Ativado':'Desativado' }}</dd></div>
<div><dt>Tempo</dt><dd>{{ $instrumento->tempo_limite_minutos??'Não definido' }} min</dd></div>
<div><dt>Tentativas</dt><dd>{{ $instrumento->tentativas_permitidas }}</dd></div>
</dl>
@if($instrumento->instrucoes)<div class="sc-panel-body"><h3>Instruções</h3><p style="white-space:pre-wrap">{{ $instrumento->instrucoes }}</p></div>@endif
</section>
</div>
<aside class="sc-aside">
@if($instrumento->status==='rascunho')
<section class="sc-side-card sc-side-gold"><h2>Adicionar questões revisadas</h2>
<p>Somente questões da mesma disciplina, já revisadas por um responsável, podem ser adicionadas.</p>
@if($questoesDisponiveis->isNotEmpty())
<form class="sc-en-form" method="post" action="{{ route('secretaria.ensino.instrumento.questoes',$instrumento) }}">@csrf
<div><label for="ins-quest">Questão</label><select id="ins-quest" name="questao_id" required><option value="">Selecione</option>
@foreach($questoesDisponiveis as $item)<option value="{{ $item->id }}">#{{ $item->id }} · {{ \Illuminate\Support\Str::limit($item->enunciado,90) }} ({{ $item->dificuldade }})</option>@endforeach
</select></div>
<div><label for="ins-pontos">Pontuação</label><input id="ins-pontos" type="number" name="pontos" min="1" max="100" value="1" required></div>
<button type="submit" class="sc-button sc-primary">Adicionar questão</button></form>
@else<p>Nenhuma questão revisada disponível. Crie ou revise questões no banco.</p>@endif
<a class="sc-text-link" href="{{ route('secretaria.ensino.questoes') }}">Abrir banco de questões</a></section>
<section class="sc-side-card"><h2>Concluir revisão administrativa</h2><p>Confirme a organização das questões. Isto não distribui o instrumento no Portal do Aluno.</p>
<form class="sc-en-form" method="post" action="{{ route('secretaria.ensino.instrumento.publicar',$instrumento) }}">@csrf
<label class="sc-en-check"><input type="checkbox" name="confirmar_publicacao" value="1" required> Conferi todas as questões e instruções</label>
<button class="sc-button sc-primary" type="submit">Marcar como revisado</button></form></section>
@else
<section class="sc-side-card sc-side-gold"><h2>Instrumento revisado</h2><p>O conteúdo está congelado para impedir alterações de gabarito após aprovação. Integração com entrega e correção do Portal do Aluno ainda não está habilitada.</p></section>
@endif
</aside>
</div>
@endsection
