@extends('secretaria.central.layout')
@section('title','Prévia do documento acadêmico')
@section('breadcrumb')<span aria-hidden="true">/</span><a href="{{ route('secretaria.documentos.painel') }}">Documentos</a><span aria-hidden="true">/</span><a href="{{ route('secretaria.documentos.declaracoes') }}">Declarações</a><span aria-hidden="true">/</span><span>Prévia</span>@endsection
@section('content')
<a class="sc-back" href="{{ route('secretaria.documentos.declaracoes') }}">@include('secretaria.central.icone',['nome'=>'voltar']) Voltar às declarações</a>
<div class="sc-intro"><div><p class="sc-eyebrow">Prévia institucional · #{{ $documento->id }}</p>
<h1>{{ \App\Models\SecretariaDocumento::TIPOS[$documento->tipo]??$documento->tipo }}</h1>
<p>{{ $dados['estudante'] }} · {{ $dados['curso'] }} · {{ $dados['numero_matricula'] }}</p></div>
<span class="sc-doc-badge {{ $documento->status==='aprovado_interno'?'ok':'warn' }}">{{ ucfirst(str_replace('_',' ',$documento->status)) }}</span></div>
@include('secretaria.central.documentos.nav',['secao'=>'declaracoes'])
<div class="sc-doc-notice"><strong>Prévia, sem validade documental.</strong> O PDF usa dados acadêmicos e símbolos institucionais, mas não contém código verificável nem assinatura digital certificada. Aprovação interna não substitui emissão oficial.</div>
<div class="sc-doc-layout">
<section class="sc-panel"><div class="sc-panel-heading"><h2>Visualizar documento A4 — {{ \App\Models\SecretariaDocumento::MODELOS[$documento->modelo]??$documento->modelo }}</h2>
<p>O mesmo modelo será apresentado ao revisor, preservando os dados congelados após envio.</p></div>
<div class="sc-panel-body">
<iframe class="sc-doc-preview" title="Prévia PDF da declaração para conferência" src="{{ route('secretaria.documentos.declaracao.pdf',$documento) }}"></iframe>
<div class="sc-doc-actions" style="margin-top:13px">
<a class="sc-button" href="{{ route('secretaria.documentos.declaracao.pdf',$documento) }}" target="_blank" rel="noopener noreferrer">Abrir PDF em outra guia</a>
</div></div></section>
<aside class="sc-aside">
<section class="sc-side-card sc-side-gold"><h2>Fluxo de revisão</h2>
<dl class="sc-doc-info" style="grid-template-columns:1fr"><div><dt>Preparado por</dt><dd>{{ $documento->preparadoPor?->name??'Não identificado' }}</dd></div>
<div><dt>Modelo</dt><dd>{{ \App\Models\SecretariaDocumento::MODELOS[$documento->modelo]??$documento->modelo }}</dd></div>
<div><dt>Situação</dt><dd>{{ ucfirst(str_replace('_',' ',$documento->status)) }}</dd></div>
@if($documento->revisado_em)<div><dt>Última revisão</dt><dd>{{ $documento->revisadoPor?->name }} · {{ $documento->revisado_em->format('d/m/Y') }}</dd></div>@endif</dl>
@if(in_array($documento->status,['rascunho','devolvido'],true))
@if($documento->preparado_por===auth()->id())
<form class="sc-doc-form" method="post" action="{{ route('secretaria.documentos.declaracao.atualizar',$documento) }}" style="margin-bottom:22px">@csrf @method('PUT')
<div><label for="doc-modelo">Modelo visual</label><select id="doc-modelo" name="modelo" required>
@foreach(\App\Models\SecretariaDocumento::MODELOS as $chave=>$rotulo)
<option value="{{ $chave }}" @selected(old('modelo',$documento->modelo)===$chave)>{{ $rotulo }}</option>
@endforeach</select></div>
<div><label for="doc-finalidade">Finalidade</label><textarea id="doc-finalidade" name="finalidade" minlength="5" maxlength="500" required>{{ old('finalidade',$documento->finalidade) }}</textarea></div>
<div><label for="doc-assinatura">Assinatura gráfica</label><select id="doc-assinatura" name="assinatura_id">
<option value="">Sem assinatura</option>
@foreach(\App\Models\SecretariaAssinatura::query()->where('responsavel_id',auth()->id())->where('ativa',true)->get() as $assinatura)
<option value="{{ $assinatura->id }}" @selected((int)old('assinatura_id',$documento->assinatura_id)===$assinatura->id)>{{ $assinatura->cargo }} · #{{ $assinatura->id }}</option>
@endforeach</select></div>
<button type="submit" class="sc-button">Salvar ajustes</button>
</form>
@endif
<form class="sc-doc-form" method="post" action="{{ route('secretaria.documentos.declaracao.enviar',$documento) }}">@csrf
<label class="sc-doc-check"><input type="checkbox" name="confirmar_envio" value="1" required> Conferi os dados e estou encaminhando apenas para aprovação interna.</label>
<button class="sc-button sc-primary" type="submit">Encaminhar para revisão</button></form>
@elseif($documento->status==='em_aprovacao')
@if($documento->preparado_por!==auth()->id())
<form class="sc-doc-form" method="post" action="{{ route('secretaria.documentos.declaracao.revisar',$documento) }}">@csrf
<div><label for="rev-decisao">Decisão</label><select id="rev-decisao" name="decisao" required><option value="">Selecione</option><option value="aprovar">Aprovar internamente</option><option value="devolver">Devolver para correção</option></select></div>
<div><label for="rev-motivo">Motivo (obrigatório ao devolver)</label><textarea id="rev-motivo" name="motivo" rows="3" maxlength="1000">{{ old('motivo') }}</textarea></div>
<label class="sc-doc-check"><input type="checkbox" name="conferiu_previa" value="1" required> Conferi a prévia A4 e os dados identificados</label>
<button class="sc-button sc-primary" type="submit">Registrar decisão interna</button></form>
@else<p style="margin-top:14px">Aguardando conferência por outro integrante autorizado da Secretaria.</p>@endif
@else<p style="margin-top:14px">Revisão interna concluída. A versão oficial ficará para uma etapa posterior.</p>@endif
</section>
@if($documento->observacao_revisao)<section class="sc-side-card"><h2>Observação de revisão</h2><p>{{ $documento->observacao_revisao }}</p></section>@endif
<section class="sc-side-card"><h2>Histórico e rastreabilidade</h2>
@forelse($historico as $evento)
<div style="border-top:1px solid #e5ecf0;padding:9px 0"><strong style="font-size:12px">{{ str_replace('_',' ',$evento->acao) }}</strong>
<p>{{ $evento->responsavel??'Conta indisponível' }} · {{ \Illuminate\Support\Carbon::parse($evento->created_at)->format('d/m/Y H:i') }}</p>
@if($evento->motivo)<p>{{ $evento->motivo }}</p>@endif</div>
@empty<p>Sem movimentações.</p>@endforelse</section>
</aside></div>
@endsection
