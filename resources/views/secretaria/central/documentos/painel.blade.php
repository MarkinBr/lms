@extends('secretaria.central.layout')
@section('title','Documentos e solicitações')
@section('breadcrumb')<span aria-hidden="true">/</span><span aria-current="page">Documentos e solicitações</span>@endsection
@section('content')
<div class="sc-intro"><div><p class="sc-eyebrow">Secretaria acadêmica</p><h1>Documentos e solicitações</h1>
<p>Acompanhe protocolos, confira documentos recebidos e prepare declarações institucionais para revisão interna.</p></div>
<a class="sc-button sc-primary" href="{{ route('secretaria.documentos.declaracoes') }}">@include('secretaria.central.icone',['nome'=>'mais']) Preparar documento</a></div>
@include('secretaria.central.documentos.nav',['secao'=>'painel'])
<dl class="sc-doc-stats">
<div class="sc-doc-stat"><span>Solicitações em atendimento</span><strong>{{ $totais['solicitacoes'] }}</strong><a href="{{ route('secretaria.documentos.solicitacoes',['status'=>'pendente']) }}">Consultar protocolos →</a></div>
<div class="sc-doc-stat"><span>Arquivos para conferir</span><strong>{{ $totais['documentos_pendentes'] }}</strong><a href="{{ route('secretaria.documentos.documentacao',['situacao'=>'pendentes']) }}">Abrir documentação →</a></div>
<div class="sc-doc-stat"><span>Declarações aguardando aprovação</span><strong>{{ $totais['declaracoes_pendentes'] }}</strong><a href="{{ route('secretaria.documentos.aprovacoes') }}">Abrir fila →</a></div>
<div class="sc-doc-stat"><span>Rascunhos / devolvidos</span><strong>{{ $totais['declaracoes_rascunho'] }}</strong><a href="{{ route('secretaria.documentos.declaracoes') }}">Continuar preparo →</a></div>
</dl>
<div class="sc-doc-layout">
<div class="sc-doc-stack">
<section class="sc-panel"><div class="sc-panel-heading"><h2>Solicitações que precisam de atendimento</h2><p>Consulte a solicitação e seus anexos antes de responder ao aluno.</p></div>
<ul class="sc-doc-list">@forelse($solicitacoes as $item)
<li><div><h3>{{ $item->assunto }} <span class="sc-doc-badge warn">{{ ucfirst(str_replace('_',' ',$item->status)) }}</span></h3>
<p>{{ $item->aluno?->name??'Aluno não identificado' }} · Protocolo {{ $item->protocolo??$item->id }} · {{ $item->created_at?->format('d/m/Y') }}</p></div>
<a class="sc-button sc-button-small" href="{{ route('secretaria.documentos.solicitacao',$item) }}">Atender →</a></li>
@empty<li><p>Nenhuma solicitação em atendimento.</p></li>@endforelse</ul>
<div class="sc-panel-note"><a class="sc-text-link" href="{{ route('secretaria.documentos.solicitacoes') }}">Ver todos os protocolos</a></div></section>
<section class="sc-panel"><div class="sc-panel-heading"><h2>Declarações na fila de aprovação</h2><p>Prévia do documento antes de aprovar ou devolver.</p></div>
<ul class="sc-doc-list">@forelse($declaracoes as $item)
<li><div><h3>{{ \App\Models\SecretariaDocumento::TIPOS[$item->tipo]??$item->tipo }}</h3>
<p>{{ $item->matricula?->user?->name??'Aluno' }} · {{ \App\Models\SecretariaDocumento::MODELOS[$item->modelo]??$item->modelo }}</p></div>
<a class="sc-button sc-button-small" href="{{ route('secretaria.documentos.declaracao',$item) }}">Visualizar →</a></li>
@empty<li><p>Nenhuma declaração aguardando revisão.</p></li>@endforelse</ul></section>
</div>
<aside class="sc-aside"><section class="sc-side-card sc-side-gold"><h2>Modelos institucionais</h2><p>Quatro padrões visuais com as logomarcas do CETSADP e da IEADP: clássico, mosaico azul, solene e minimalista.</p><a class="sc-text-link" href="{{ route('secretaria.documentos.declaracoes') }}">Escolher modelo →</a></section>
<section class="sc-side-card"><h2>Assinaturas gráficas</h2><p>Cadastre a própria assinatura em PNG/JPG/WebP ou desenhe com o dedo no celular. O cadastro não é certificação digital.</p><a class="sc-text-link" href="{{ route('secretaria.documentos.assinaturas') }}">Gerenciar assinaturas →</a></section>
<section class="sc-side-card"><h2>Liberação responsável</h2><p>A aprovação interna exige outro membro da equipe. A prévia mantém marcação explícita de que não é documento emitido nem possui QR de validação.</p></section></aside>
</div>
@endsection
