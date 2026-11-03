@extends('secretaria.central.layout')
@section('title','Protocolos e histórico')
@section('breadcrumb')<span aria-hidden="true">/</span><a href="{{ route('secretaria.documentos.painel') }}">Documentos</a><span aria-hidden="true">/</span><span>Histórico</span>@endsection
@section('content')
<div class="sc-intro"><div><p class="sc-eyebrow">Rastreabilidade</p><h1>Protocolos e histórico</h1><p>Movimentações realizadas nesta Central, incluindo conferência de documentos e revisão de declarações.</p></div></div>
@include('secretaria.central.documentos.nav',['secao'=>'historico'])
<section class="sc-panel"><div class="sc-panel-heading"><h2>Trilha operacional</h2>
<form class="sc-doc-filters" method="get" action="{{ route('secretaria.documentos.historico') }}">
<select aria-label="Tipo de registro" name="entidade"><option value="">Todas as operações</option>
@foreach(['declaracao'=>'Declarações','documento_inscricao'=>'Documentação','requerimento'=>'Solicitações'] as $k=>$rotulo)<option value="{{ $k }}" @selected($entidade===$k)>{{ $rotulo }}</option>@endforeach</select>
<button type="submit" class="sc-button sc-primary">Filtrar</button></form></div>
<div class="sc-doc-tablewrap"><table class="sc-doc-table"><thead><tr><th>Data</th><th>Registro</th><th>Operação</th><th>Situação</th><th>Responsável</th><th>Justificativa</th></tr></thead><tbody>
@forelse($movimentacoes as $evento)<tr>
<td>{{ \Illuminate\Support\Carbon::parse($evento->created_at)->format('d/m/Y H:i') }}</td>
<td><strong>{{ ucfirst(str_replace('_',' ',$evento->entidade)) }}</strong><small>#{{ $evento->entidade_id }}</small></td>
<td>{{ str_replace('_',' ',$evento->acao) }}</td>
<td>{{ $evento->estado_anterior??'—' }} → {{ $evento->estado_novo??'—' }}</td>
<td>{{ $evento->responsavel??'Conta removida' }}</td>
<td>{{ $evento->motivo??'—' }}</td></tr>
@empty<tr><td colspan="6">Nenhuma movimentação registrada.</td></tr>@endforelse</tbody></table></div>
<div class="sc-panel-body">{{ $movimentacoes->links() }}</div></section>
@endsection
