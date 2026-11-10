@extends('secretaria.central.layout')
@section('title','Declarações e emissões')
@section('breadcrumb')<span aria-hidden="true">/</span><a href="{{ route('secretaria.documentos.painel') }}">Documentos</a><span aria-hidden="true">/</span><span>Declarações e emissões</span>@endsection
@section('content')
<div class="sc-intro"><div><p class="sc-eyebrow">Documentos acadêmicos</p><h1>Declarações e emissões</h1>
<p>Prepare documentos em quatro modelos institucionais, confira a prévia A4 e encaminhe para aprovação interna.</p></div></div>
@include('secretaria.central.documentos.nav',['secao'=>'declaracoes'])
<div class="sc-doc-notice"><strong>Versão inicial aprovada com ressalvas.</strong> Esta etapa cria prévias e registra revisões internas. Não gera documento com autenticidade oficial nem QR de validação. Declarações de frequência, conclusão, histórico e segunda via serão integradas após definição de suas regras específicas.</div>
<div class="sc-doc-layout">
<div class="sc-doc-stack">
<section class="sc-panel"><div class="sc-panel-heading"><h2>Documentos preparados</h2>
<div class="sc-doc-actions" style="margin-top:12px">@foreach([''=>'Todos','rascunho'=>'Rascunhos','em_aprovacao'=>'Para aprovação','aprovado_interno'=>'Aprovados internamente','devolvido'=>'Devolvidos'] as $k=>$rotulo)
<a class="sc-button sc-button-small" href="{{ route('secretaria.documentos.declaracoes',['situacao'=>$k]) }}">{{ $rotulo }}</a>@endforeach</div>
</div>
<ul class="sc-doc-list">@forelse($documentos as $item)<li>
<div><h3>{{ \App\Models\SecretariaDocumento::TIPOS[$item->tipo]??$item->tipo }} <span class="sc-doc-badge {{ $item->status==='aprovado_interno'?'ok':($item->status==='devolvido'?'bad':'warn') }}">{{ ucfirst(str_replace('_',' ',$item->status)) }}</span></h3>
<p>{{ $item->matricula?->user?->name??'Aluno não informado' }} · {{ $item->matricula?->curso?->titulo??'Curso' }}</p>
<p>Modelo {{ \App\Models\SecretariaDocumento::MODELOS[$item->modelo]??$item->modelo }} · {{ $item->created_at?->format('d/m/Y') }}</p></div>
<a class="sc-button sc-button-small" href="{{ route('secretaria.documentos.declaracao',$item) }}">Ver documento →</a></li>
@empty<li>Nenhum documento preparado.</li>@endforelse</ul><div class="sc-panel-body">{{ $documentos->links() }}</div></section>
<section class="sc-panel"><div class="sc-panel-heading"><h2>Quatro modelos institucionais</h2><p>Modelos guardados individualmente em cada documento.</p></div>
<div class="sc-panel-body sc-doc-template-list">
@foreach(\App\Models\SecretariaDocumento::MODELOS as $key=>$rotulo)
<div class="sc-doc-template"><span class="dot"></span><b>{{ $rotulo }}</b>
<p>{{ [
'classico'=>'Moldura tradicional com logomarcas oficiais.',
'mosaico'=>'Elementos geométricos azuis, pomba e elementos da identidade visual.',
'solene'=>'Azul-marinho e dourado, faixa de título solene.',
'minimalista'=>'Moldura discreta, composição tradicional e marca-d’água.'
][$key] }}</p></div>
@endforeach</div></section>
</div>
<aside class="sc-aside"><section class="sc-side-card sc-side-gold"><h2>Preparar documento</h2>
<form method="post" class="sc-doc-form" action="{{ route('secretaria.documentos.declaracoes.preparar') }}">@csrf
<div><label for="decl-matricula">Matrícula acadêmica</label><select name="matricula_id" id="decl-matricula" required>
<option value="">Selecione o aluno / curso</option>@foreach($matriculas as $mat)<option value="{{ $mat->id }}" @selected(old('matricula_id')==$mat->id)>{{ $mat->user?->name??'Aluno' }} · {{ $mat->curso?->titulo??'Curso' }} · {{ $mat->numero_matricula??'S/N' }}</option>@endforeach
</select><small>Lista com as 500 matrículas recentes. Em uma revisão posterior, será substituída por busca paginada.</small></div>
<div><label for="decl-tipo">Tipo de documento</label><select name="tipo" id="decl-tipo" required>
@foreach(\App\Models\SecretariaDocumento::TIPOS as $key=>$rotulo)<option value="{{ $key }}" @selected(old('tipo')===$key)>{{ $rotulo }}</option>@endforeach
</select></div>
<div><label for="decl-modelo">Modelo visual</label><select name="modelo" id="decl-modelo" required>
@foreach(\App\Models\SecretariaDocumento::MODELOS as $key=>$rotulo)<option value="{{ $key }}" @selected(old('modelo','classico')===$key)>{{ $rotulo }}</option>@endforeach
</select></div>
<div><label for="decl-finalidade">Finalidade</label><textarea name="finalidade" id="decl-finalidade" minlength="5" maxlength="500" rows="3" required placeholder="Ex.: comprovação de estudos">{{ old('finalidade') }}</textarea></div>
<div><label for="decl-assinatura">Sua assinatura gráfica (opcional)</label><select name="assinatura_id" id="decl-assinatura"><option value="">Sem assinatura</option>
@foreach($assinaturas as $ass)<option value="{{ $ass->id }}" @selected(old('assinatura_id')==$ass->id)>{{ $ass->cargo }} · #{{ $ass->id }}</option>@endforeach
</select><small>Assinatura de imagem sem valor de assinatura digital certificada.</small></div>
<button class="sc-button sc-primary" type="submit">Criar rascunho</button>
</form></section>
<section class="sc-side-card"><h2>Modelos que virão depois</h2><p>Declaração de frequência, conclusão, histórico escolar e certificado exigem conferência de regras acadêmicas específicas. A geração oficial não foi ativada.</p></section>
</aside></div>
@endsection
