@extends('secretaria.central.layout')
@section('title','Aprovações')
@section('breadcrumb')<span aria-hidden="true">/</span><a href="{{ route('secretaria.documentos.painel') }}">Documentos</a><span aria-hidden="true">/</span><span>Aprovações</span>@endsection
@section('content')
<div class="sc-intro"><div><p class="sc-eyebrow">Conferência antes de liberar</p><h1>Fila de aprovações</h1><p>Documentos pessoais e declarações preparadas, sempre com visualização antes de decidir.</p></div></div>
@include('secretaria.central.documentos.nav',['secao'=>'aprovacoes'])
<div class="sc-doc-stack">
<section class="sc-panel"><div class="sc-panel-heading"><h2>Declarações aguardando aprovação interna</h2>
<p>É necessário conferir o modelo e o conteúdo. Quem preparou não pode aprovar o próprio documento.</p></div>
<ul class="sc-doc-list">@forelse($declaracoes as $item)<li>
<div><h3>{{ \App\Models\SecretariaDocumento::TIPOS[$item->tipo]??$item->tipo }} <span class="sc-doc-badge warn">Aguardando revisão</span></h3>
<p>{{ $item->matricula?->user?->name??'Aluno' }} · {{ \App\Models\SecretariaDocumento::MODELOS[$item->modelo]??$item->modelo }} · Preparado por {{ $item->preparadoPor?->name??'Responsável' }}</p></div>
<a class="sc-button sc-button-small" href="{{ route('secretaria.documentos.declaracao',$item) }}">Visualizar prévia e revisar →</a>
</li>@empty<li><p>Nenhuma declaração pendente.</p></li>@endforelse</ul>
<div class="sc-panel-body">{{ $declaracoes->links() }}</div></section>
<section class="sc-panel"><div class="sc-panel-heading"><h2>Documentação de inscrições pendente</h2><p>Abra cada arquivo recebido antes de aprovar ou rejeitar.</p></div>
<ul class="sc-doc-list">@forelse($documentos as $item)<li>
<div><h3>{{ $item->application?->nome_completo??'Candidato' }} · {{ $item->tipo_label }}</h3><p>{{ $item->nome_original }} · Enviado em {{ $item->created_at?->format('d/m/Y') }}</p></div>
<a class="sc-button sc-button-small" href="{{ route('secretaria.documentos.documentacao.conferir',$item) }}">Conferir arquivo →</a>
</li>@empty<li><p>Nenhum documento pessoal pendente.</p></li>@endforelse</ul></section>
</div>
@endsection
