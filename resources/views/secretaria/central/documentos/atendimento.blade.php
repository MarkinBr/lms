@extends('secretaria.central.layout')
@section('title','Atendimento ao aluno')
@section('breadcrumb')<span aria-hidden="true">/</span><a href="{{ route('secretaria.documentos.painel') }}">Documentos</a><span aria-hidden="true">/</span><span>Atendimento</span>@endsection
@section('content')
<div class="sc-intro"><div><p class="sc-eyebrow">Visão do estudante</p><h1>Atendimento ao aluno</h1><p>Localize o estudante e consulte seus vínculos acadêmicos e protocolos sem alterar suas credenciais de acesso.</p></div></div>
@include('secretaria.central.documentos.nav',['secao'=>'atendimento'])
<section class="sc-panel"><div class="sc-panel-heading"><h2>Localizar aluno</h2>
<form class="sc-doc-filters" method="get" action="{{ route('secretaria.documentos.atendimento') }}">
<input type="search" name="busca" aria-label="Nome, e-mail ou matrícula de acesso" value="{{ $busca }}" placeholder="Nome, e-mail ou matrícula de acesso">
<button type="submit" class="sc-button sc-primary">Pesquisar</button></form></div>
<div class="sc-doc-tablewrap"><table class="sc-doc-table"><thead><tr><th>Aluno</th><th>Contato</th><th>Matrículas</th><th>Solicitações</th><th>Atendimento</th></tr></thead><tbody>
@forelse($alunos as $aluno)<tr>
<td><strong>{{ $aluno->name }}</strong><small>ID: {{ $aluno->id }}</small></td><td>{{ $aluno->email }}</td>
<td>{{ $aluno->matriculas_count }}</td><td>{{ $aluno->requerimentos_count }}</td>
<td><a class="sc-button sc-button-small" href="{{ route('secretaria.acessos.show',$aluno) }}">Abrir ficha do aluno →</a></td>
</tr>@empty<tr><td colspan="5">Nenhum aluno localizado.</td></tr>@endforelse</tbody></table></div>
<div class="sc-panel-body">{{ $alunos->links() }}</div></section>
@endsection
