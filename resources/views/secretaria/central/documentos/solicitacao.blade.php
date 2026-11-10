@extends('secretaria.central.layout')
@section('title','Atendimento ao protocolo')
@section('breadcrumb')<span aria-hidden="true">/</span><a href="{{ route('secretaria.documentos.painel') }}">Documentos</a><span aria-hidden="true">/</span><a href="{{ route('secretaria.documentos.solicitacoes') }}">Solicitações</a><span aria-hidden="true">/</span><span>Protocolo</span>@endsection
@section('content')
<a class="sc-back" href="{{ route('secretaria.documentos.solicitacoes') }}">@include('secretaria.central.icone',['nome'=>'voltar']) Voltar às solicitações</a>
<div class="sc-intro"><div><p class="sc-eyebrow">Protocolo {{ $requerimento->protocolo??'#'.$requerimento->id }}</p><h1>{{ $requerimento->assunto }}</h1>
<p>{{ $requerimento->aluno?->name??'Aluno não identificado' }} · {{ $requerimento->created_at?->format('d/m/Y H:i') }} · {{ str_replace('_',' ',$requerimento->tipo) }}</p></div>
<span class="sc-doc-badge warn">{{ ucfirst(str_replace('_',' ',$requerimento->status)) }}</span></div>
@include('secretaria.central.documentos.nav',['secao'=>'solicitacoes'])
<div class="sc-doc-layout"><div class="sc-doc-stack">
<section class="sc-panel"><div class="sc-panel-heading"><h2>Descrição e anexos</h2></div><div class="sc-panel-body">
<p style="white-space:pre-line">{{ $requerimento->descricao }}</p>
@if($requerimento->anexo)
<div class="sc-doc-notice" style="margin-top:20px">O protocolo legado contém um anexo. A visualização de arquivos legados será integrada por rota privada em uma revisão posterior. Nenhum link público é exposto nesta Central.</div>
@endif
@if($requerimento->anexos)<div style="margin-top:16px"><h3>Outros anexos</h3><p class="sc-doc-mini">Anexos registrados no atendimento antigo. A conferência privada depende da migração de armazenamento.</p></div>@endif
</div></section>
@if($requerimento->resposta)
<section class="sc-panel"><div class="sc-panel-heading"><h2>Última resposta</h2></div><div class="sc-panel-body">
<p style="white-space:pre-line">{{ $requerimento->resposta }}</p>
<p class="sc-doc-mini">{{ $requerimento->respondidoPor?->name??'Responsável' }} · {{ $requerimento->respondido_em?->format('d/m/Y H:i') }}</p>
</div></section>
@endif
</div><aside class="sc-aside"><section class="sc-side-card sc-side-gold"><h2>Responder ao atendimento</h2>
@if(in_array($requerimento->status,['pendente','em_analise'],true))
<form class="sc-doc-form" method="post" action="{{ route('secretaria.documentos.solicitacao.responder',$requerimento) }}">@csrf
<div><label for="req-status">Nova situação</label><select id="req-status" name="status" required><option value="">Selecione</option>
@foreach(['em_analise'=>'Em análise','resolvido'=>'Resolvido','negado'=>'Negado'] as $k=>$rotulo)<option value="{{ $k }}" @selected(old('status')===$k)>{{ $rotulo }}</option>@endforeach
</select></div>
<div><label for="req-resposta">Resposta / justificativa</label><textarea id="req-resposta" name="resposta" minlength="5" maxlength="3000" rows="5" required>{{ old('resposta') }}</textarea></div>
<label class="sc-doc-check"><input type="checkbox" name="confirmar_analise" value="1" required> Conferi os dados e a situação do protocolo</label>
<button class="sc-button sc-primary" type="submit">Registrar resposta</button></form>
@else<p>Protocolo finalizado. O histórico foi preservado e não pode ser alterado nesta tela.</p>@endif</section>
<section class="sc-side-card"><h2>Atendimento seguro</h2><p>O gerenciamento do documento emitido é separado da resposta ao protocolo. A resposta não gera declaração oficial automaticamente.</p></section></aside></div>
@endsection
