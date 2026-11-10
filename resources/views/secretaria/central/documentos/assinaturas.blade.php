@extends('secretaria.central.layout')
@section('title','Assinaturas gráficas')
@section('breadcrumb')<span aria-hidden="true">/</span><a href="{{ route('secretaria.documentos.painel') }}">Documentos</a><span aria-hidden="true">/</span><span>Assinaturas</span>@endsection
@section('content')
<div class="sc-intro"><div><p class="sc-eyebrow">Identificação da equipe</p><h1>Cadastro de assinaturas</h1><p>Importe a sua assinatura manuscrita em imagem ou desenhe com o dedo diretamente na tela do celular.</p></div></div>
@include('secretaria.central.documentos.nav',['secao'=>'assinaturas'])
<div class="sc-doc-notice">Assinaturas gráficas são destinadas apenas à identificação visual nas prévias. Não representam assinatura digital certificada nem autorização para assinar em nome de outra pessoa. Os arquivos ficam privados, vinculados à sua conta.</div>
<div class="sc-doc-layout">
<div class="sc-doc-stack"><section class="sc-panel"><div class="sc-panel-heading"><h2>Minhas assinaturas cadastradas</h2><p>Desative uma assinatura para impedir seu uso em novos documentos. Os registros anteriores serão preservados.</p></div>
<ul class="sc-doc-list">@forelse($assinaturas as $assinatura)<li>
<div><h3>{{ $assinatura->responsavel?->name??auth()->user()->name }} · {{ $assinatura->cargo }}</h3>
<p>Origem: {{ $assinatura->origem==='touch'?'Assinada no touch':'Arquivo enviado' }} · {{ $assinatura->created_at?->format('d/m/Y') }}</p>
@if($assinatura->ativa)<img class="sc-doc-signature" src="{{ route('secretaria.documentos.assinaturas.imagem',$assinatura) }}" alt="Assinatura gráfica cadastrada">@endif
</div><div class="sc-doc-actions"><span class="sc-doc-badge {{ $assinatura->ativa?'ok':'bad' }}">{{ $assinatura->ativa?'Ativa':'Desativada' }}</span>
@if($assinatura->ativa)<form method="post" action="{{ route('secretaria.documentos.assinaturas.desativar',$assinatura) }}">@csrf
<button type="submit" class="sc-button sc-button-small">Desativar</button></form>@endif</div></li>
@empty<li>Nenhuma assinatura cadastrada.</li>@endforelse</ul></section>
</div>
<aside class="sc-aside">
<section class="sc-side-card sc-side-gold"><h2>Nova assinatura</h2>
<form method="post" enctype="multipart/form-data" id="form-assinatura" class="sc-doc-form" action="{{ route('secretaria.documentos.assinaturas.salvar') }}">@csrf
<div><label>Responsável</label><p>{{ auth()->user()->name }}</p><small>Vinculada exclusivamente ao usuário autenticado.</small></div>
<div><label for="as-cargo">Cargo ou função</label><input name="cargo" id="as-cargo" type="text" required minlength="3" maxlength="150" value="{{ old('cargo') }}" placeholder="Secretaria Acadêmica"></div>
<div><label for="as-imagem">Enviar imagem (PNG, JPG, WebP)</label><input name="imagem" type="file" id="as-imagem" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp"><small>Até 2 MB. Não envie SVG ou documento executável.</small></div>
<div><label for="canvas-assinatura">Ou desenhar com touch / mouse</label>
<canvas id="canvas-assinatura" class="sc-doc-canvas" width="640" height="240" aria-label="Campo para desenhar sua assinatura"></canvas>
<input type="hidden" name="desenho" id="as-desenho">
<div class="sc-doc-actions" style="margin-top:9px"><button class="sc-button sc-button-small" id="limpar-assinatura" type="button">Limpar desenho</button></div>
<small>Use apenas uma opção: arquivo enviado OU desenho.</small></div>
<label class="sc-doc-check"><input type="checkbox" name="confirmar_titularidade" value="1" required> Confirmo que a assinatura gráfica cadastrada é minha e será utilizada somente em prévias internas.</label>
<button class="sc-button sc-primary" type="submit">Guardar assinatura privada</button>
</form></section>
</aside></div>
<script src="{{ asset('js/secretaria-assinaturas.js') }}?v={{ filemtime(public_path('js/secretaria-assinaturas.js')) }}" defer></script>
@endsection
