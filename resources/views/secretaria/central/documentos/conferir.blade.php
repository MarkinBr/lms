@extends('secretaria.central.layout')
@section('title','Conferir documento de inscrição')
@section('breadcrumb')<span aria-hidden="true">/</span><a href="{{ route('secretaria.documentos.painel') }}">Documentos</a><span aria-hidden="true">/</span><a href="{{ route('secretaria.documentos.documentacao') }}">Documentação</a><span aria-hidden="true">/</span><span>Conferência</span>@endsection
@section('content')
<a class="sc-back" href="{{ route('secretaria.documentos.documentacao') }}">@include('secretaria.central.icone',['nome'=>'voltar']) Voltar à documentação</a>
<div class="sc-intro"><div><p class="sc-eyebrow">Conferência privada</p><h1>{{ $documento->tipo_label }}</h1><p>{{ $documento->application?->nome_completo??'Estudante' }} · {{ $documento->nome_original }}</p></div>
<span class="sc-doc-badge {{ $documento->validado?'ok':'warn' }}">{{ $documento->validado?'Aprovado':($documento->validado_por_id?'Rejeitado':'Pendente') }}</span></div>
@include('secretaria.central.documentos.nav',['secao'=>'documentacao'])
<div class="sc-doc-layout"><section class="sc-panel"><div class="sc-panel-heading"><h2>Prévia do arquivo recebido</h2>
<p>Visualização restrita à equipe autorizada, sem link público de armazenamento.</p></div>
<div class="sc-panel-body">
<iframe class="sc-doc-preview" title="Visualização do documento enviado" src="{{ route('secretaria.documentos.documentacao.arquivo',$documento) }}"></iframe>
<p class="sc-doc-mini" style="margin-top:10px"><a class="sc-text-link" href="{{ route('secretaria.documentos.documentacao.arquivo',$documento) }}" target="_blank" rel="noopener noreferrer">Abrir arquivo isoladamente ↗</a></p>
</div></section>
<aside class="sc-aside"><section class="sc-side-card sc-side-gold"><h2>Registrar conferência</h2>
@if($documento->validado_em===null)
<form class="sc-doc-form" method="post" action="{{ route('secretaria.documentos.documentacao.decidir',$documento) }}">@csrf
<div><label for="doc-decisao">Resultado</label><select name="decisao" id="doc-decisao" required><option value="">Selecione</option><option value="aprovar">Aprovar arquivo</option><option value="rejeitar">Rejeitar e solicitar correção</option></select></div>
<div><label for="doc-motivo">Justificativa (obrigatória para rejeição)</label><textarea name="motivo" id="doc-motivo" maxlength="1000" rows="4" placeholder="Descreva o que deve ser corrigido">{{ old('motivo') }}</textarea></div>
<label class="sc-doc-check"><input type="checkbox" name="conferiu_arquivo" value="1" required> Conferi a prévia acima e a identificação do estudante</label>
<button class="sc-button sc-primary" type="submit">Confirmar decisão</button></form>
@else<p>Este documento já foi analisado em {{ $documento->validado_em?->format('d/m/Y H:i') }}.</p><p style="margin-top:11px">{{ $documento->observacoes_validacao }}</p>@endif
</section><section class="sc-side-card"><h2>Proteção de dados</h2><p>Documentos pessoais devem ser consultados apenas durante o atendimento. Evite salvar cópias em dispositivos compartilhados.</p></section></aside></div>
@endsection
