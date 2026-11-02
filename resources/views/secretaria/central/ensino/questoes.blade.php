@extends('secretaria.central.layout')
@section('title','Banco de questões')
@section('breadcrumb')<span aria-hidden="true">/</span><a href="{{ route('secretaria.ensino.painel') }}">Ensino e aprendizagem</a><span aria-hidden="true">/</span><span>Banco de questões</span>@endsection
@section('content')
<div class="sc-intro"><div><p class="sc-eyebrow">Elaboração pedagógica</p><h1>Banco de questões</h1><p>Crie, revise e reutilize questões com níveis fácil, médio e difícil e referências verificáveis aos conteúdos.</p></div>
<a class="sc-button" href="{{ route('secretaria.ensino.avaliacoes') }}">Quiz, provas e simulados →</a></div>
@include('secretaria.central.ensino.nav',['secao'=>'questoes'])
<div class="sc-en-two"><section class="sc-panel"><div class="sc-panel-heading"><h2>Questões cadastradas</h2>
<form class="sc-en-filters" method="get" action="{{ route('secretaria.ensino.questoes') }}">
<input type="search" name="busca" aria-label="Texto da questão" value="{{ $f['busca'] }}" placeholder="Buscar enunciado">
<select name="disciplina" aria-label="Disciplina"><option value="">Todas as disciplinas</option>@foreach($disciplinas as $disc)<option value="{{ $disc->id }}" @selected($f['disciplina']===$disc->id)>{{ $disc->nome }}</option>@endforeach</select>
<select name="dificuldade" aria-label="Dificuldade"><option value="">Todos os níveis</option>@foreach(['facil'=>'Fácil','medio'=>'Médio','dificil'=>'Difícil'] as $k=>$label)<option value="{{ $k }}" @selected($dificuldade===$k)>{{ $label }}</option>@endforeach</select>
<button class="sc-button sc-primary">Filtrar</button></form></div>
<ul class="sc-en-list">@forelse($questoesLista as $q)<li><div>
<h3>{{ \Illuminate\Support\Str::limit($q->enunciado,145) }}</h3>
<p>{{ $q->disciplina?->nome ?? 'Disciplina' }} · {{ str_replace('_',' ',ucfirst($q->tipo)) }} · {{ ucfirst($q->dificuldade) }} · {{ $q->origem==='texto_assistido'?'Rascunho de texto':'Cadastro manual' }}</p>
<span class="sc-en-badge {{ $q->status==='rascunho'?'warn':'' }}">{{ ucfirst($q->status) }}</span>
@if($q->referencia)<p class="sc-en-source">{{ \Illuminate\Support\Str::limit($q->referencia,300) }}</p>@endif
</div><a class="sc-button sc-button-small" href="{{ route('secretaria.ensino.questoes',['editar'=>$q->id]) }}">Editar →</a></li>@empty<li>Nenhuma questão encontrada.</li>@endforelse</ul>
<div class="sc-panel-body">{{ $questoesLista->links() }}</div></section>
<aside class="sc-aside"><section class="sc-side-card sc-side-gold"><h2>{{ $edicao ? 'Revisar questão #'.$edicao->id : 'Cadastrar questão' }}</h2>
<form class="sc-en-form" method="post" action="{{ $edicao ? route('secretaria.ensino.questoes.atualizar',$edicao) : route('secretaria.ensino.questoes.salvar') }}">
@csrf @if($edicao) @method('PUT') @endif
<div><label for="q-disc">Disciplina</label><select id="q-disc" name="disciplina_id" required><option value="">Selecione</option>@foreach($disciplinas as $disc)<option value="{{ $disc->id }}" @selected(old('disciplina_id',$edicao?->disciplina_id)==$disc->id)>{{ $disc->nome }}</option>@endforeach</select></div>
<div><label for="q-aula">Aula de referência (opcional)</label><select id="q-aula" name="aula_id"><option value="">Sem aula específica</option>@foreach($aulas as $aula)<option value="{{ $aula->id }}" @selected(old('aula_id',$edicao?->aula_id)==$aula->id)>{{ $aula->titulo }}</option>@endforeach</select></div>
<div><label for="q-tipo">Tipo</label><select id="q-tipo" name="tipo" required>
@foreach(['unica'=>'Múltipla escolha (uma correta)','multipla'=>'Múltipla escolha (várias corretas)','verdadeiro_falso'=>'Verdadeiro / falso','dissertativa'=>'Dissertativa'] as $k=>$label)
<option value="{{ $k }}" @selected(old('tipo',$edicao?->tipo??'unica')===$k)>{{ $label }}</option>@endforeach</select></div>
<div><label for="q-dificuldade">Dificuldade</label><select name="dificuldade" id="q-dificuldade" required>@foreach(['facil'=>'Fácil','medio'=>'Médio','dificil'=>'Difícil'] as $k=>$label)<option value="{{ $k }}" @selected(old('dificuldade',$edicao?->dificuldade??'medio')===$k)>{{ $label }}</option>@endforeach</select></div>
<div><label for="q-enunciado">Enunciado</label><textarea id="q-enunciado" name="enunciado" rows="4" minlength="12" maxlength="4000" required>{{ old('enunciado',$edicao?->enunciado) }}</textarea></div>
<div><label>Alternativas A, B, C, D (para múltipla escolha)</label>
@foreach(['A','B','C','D'] as $letra)
<input aria-label="Alternativa {{ $letra }}" style="margin-bottom:7px" name="alternativas[{{ $letra }}]" placeholder="{{ $letra }} — texto da opção" maxlength="1000" value="{{ old('alternativas.'.$letra,$edicao?->alternativas[$letra]??'') }}">
@endforeach</div>
<div><label>Gabarito (selecione uma ou várias alternativas)</label>
<div class="sc-en-inline">@foreach(['A','B','C','D','V','F'] as $valor)<label class="sc-en-choice"><input type="checkbox" name="gabarito[]" value="{{ $valor }}" @checked(in_array($valor,old('gabarito',$edicao?->gabarito??[]),true))>{{ $valor }}</label>@endforeach</div>
<small>Uma correta no tipo simples, duas ou três no tipo múltiplo, V ou F no verdadeiro/falso. Na dissertativa, deixe sem gabarito.</small></div>
<div><label for="q-explicacao">Explicação / critério de correção</label><textarea id="q-explicacao" name="explicacao" rows="3">{{ old('explicacao',$edicao?->explicacao) }}</textarea></div>
<div><label for="q-fonte">Referência ao material ou vídeo</label><textarea id="q-fonte" name="referencia" rows="2">{{ old('referencia',$edicao?->referencia) }}</textarea></div>
<label class="sc-en-check"><input name="revisada" type="checkbox" value="1" @checked(old('revisada',$edicao?->status==='revisada'))> Revisei a questão, suas alternativas e o gabarito</label>
<button class="sc-button sc-primary" type="submit">{{ $edicao ? 'Salvar revisão' : 'Criar questão' }}</button>
@if($edicao)<a class="sc-text-link" href="{{ route('secretaria.ensino.questoes') }}">Cancelar edição</a>@endif
</form></section>
<section class="sc-side-card"><h2>Criador assistido a partir de materiais</h2><p>Selecione uma aula com texto ou transcrição revisada. O sistema seleciona trechos rastreáveis para orientar rascunhos dissertativos.</p>
<form class="sc-en-form" method="post" action="{{ route('secretaria.ensino.questoes.sugerir') }}">@csrf
<div><label for="q-sug-aula">Aula fonte</label><select id="q-sug-aula" name="aula_id" required><option value="">Selecione</option>@foreach($aulas as $aula)<option value="{{ $aula->id }}">{{ $aula->titulo }}</option>@endforeach</select></div>
<div><label for="q-sug-dif">Nível</label><select name="dificuldade" id="q-sug-dif">@foreach(['facil'=>'Fácil','medio'=>'Médio','dificil'=>'Difícil'] as $k=>$label)<option value="{{ $k }}">{{ $label }}</option>@endforeach</select></div>
<div><label for="q-sug-num">Quantidade de rascunhos</label><input type="number" name="quantidade" id="q-sug-num" value="2" min="1" max="3"></div>
<button class="sc-button" type="submit">Preparar rascunhos do texto</button></form>
<p class="sc-en-note" style="margin-top:12px">Não é IA nem analisa links de vídeo/PDF. Exige transcrição ou texto. Os rascunhos não são publicados nem recebem gabarito inventado.</p></section></aside>
</div>
@endsection
