@extends('secretaria.central.layout')
@section('title','Solicitações')
@section('breadcrumb')<span aria-hidden="true">/</span><a href="{{ route('secretaria.documentos.painel') }}">Documentos</a><span aria-hidden="true">/</span><span>Solicitações</span>@endsection
@section('content')
<div class="sc-intro"><div><p class="sc-eyebrow">Atendimento por protocolo</p><h1>Solicitações dos alunos</h1><p>Consulte declarações solicitadas, trancamentos, revisões e outras demandas registradas no Portal do Aluno.</p></div></div>
@include('secretaria.central.documentos.nav',['secao'=>'solicitacoes'])
<section class="sc-panel"><div class="sc-panel-heading"><h2>Localizar solicitação</h2>
<form class="sc-doc-filters" method="get" action="{{ route('secretaria.documentos.solicitacoes') }}"><input aria-label="Pesquisar protocolo, aluno ou assunto" type="search" name="busca" value="{{ $busca }}" placeholder="Protocolo, aluno ou assunto">
<select aria-label="Situação" name="status"><option value="">Todas as situações</option>
@foreach(['pendente'=>'Pendente','em_analise'=>'Em análise','resolvido'=>'Resolvido','aprovado'=>'Aprovado (legado)','negado'=>'Negado','cancelado'=>'Cancelado'] as $k=>$rotulo)
<option value="{{ $k }}" @selected($status===$k)>{{ $rotulo }}</option>@endforeach</select>
<button class="sc-button sc-primary" type="submit">Pesquisar</button></form>
</div><div class="sc-doc-tablewrap"><table class="sc-doc-table"><thead><tr><th>Protocolo</th><th>Aluno</th><th>Assunto</th><th>Situação</th><th>Ação</th></tr></thead><tbody>
@forelse($solicitacoes as $item)<tr>
<td><strong>{{ $item->protocolo??'#'.$item->id }}</strong><small>{{ $item->created_at?->format('d/m/Y H:i') }}</small></td>
<td>{{ $item->aluno?->name??'Não identificado' }}</td><td><strong>{{ $item->assunto }}</strong><small>{{ str_replace('_',' ',$item->tipo) }}</small></td>
<td><span class="sc-doc-badge {{ in_array($item->status,['resolvido','aprovado'],true)?'ok':($item->status==='negado'?'bad':'warn') }}">{{ ucfirst(str_replace('_',' ',$item->status)) }}</span></td>
<td><a class="sc-button sc-button-small" href="{{ route('secretaria.documentos.solicitacao',$item) }}">Abrir →</a></td>
</tr>@empty<tr><td colspan="5">Nenhuma solicitação encontrada.</td></tr>@endforelse</tbody></table></div>
<div class="sc-panel-body">{{ $solicitacoes->links() }}</div></section>
@endsection
