@extends('secretaria.central.layout')
@section('title','Notas e resultados')
@section('breadcrumb')<span aria-hidden="true">/</span><a href="{{ route('secretaria.ensino.painel') }}">Ensino e aprendizagem</a><span aria-hidden="true">/</span><span>Notas</span>@endsection
@section('content')
<div class="sc-intro"><div><p class="sc-eyebrow">Avaliação da aprendizagem</p><h1>Notas e resultados</h1><p>Consulte o boletim legado e as médias registradas dos alunos, sem recalcular resultados automaticamente.</p></div></div>
@include('secretaria.central.ensino.nav',['secao'=>'notas'])
<div class="sc-en-banner">O cadastro de provas e simulados deste módulo não lança notas automaticamente no boletim acadêmico. Regras de lançamento e recuperação devem ser conferidas antes da integração.</div>
<section class="sc-panel"><div class="sc-panel-heading"><h2>Boletim acadêmico existente</h2>
<form class="sc-en-filters" method="get" action="{{ route('secretaria.ensino.notas') }}"><input type="search" name="busca" aria-label="Pesquisar aluno ou disciplina" value="{{ $busca }}" placeholder="Nome do aluno ou disciplina">
<button class="sc-button sc-primary">Buscar</button></form></div>
<div class="sc-en-tablewrap"><table class="sc-en-table"><thead><tr><th>Aluno</th><th>Disciplina</th><th>AV1</th><th>AV2</th><th>Recuperação</th><th>Média final</th><th>Situação</th></tr></thead><tbody>
@forelse($notas as $nota)<tr><td><strong>{{ $nota->aluno?->name ?? 'Aluno não encontrado' }}</strong></td><td>{{ $nota->disciplina ?? 'Não informada' }}</td>
<td>{{ $nota->nota_av1??'—' }}</td><td>{{ $nota->nota_av2??'—' }}</td><td>{{ $nota->nota_av3??'—' }}</td><td><strong>{{ $nota->media_final??'—' }}</strong></td>
<td><span class="sc-en-badge neutral">{{ $nota->situacao??'Não calculada' }}</span></td></tr>
@empty<tr><td colspan="7">Nenhuma nota localizada.</td></tr>@endforelse</tbody></table></div>
<div class="sc-panel-body">{{ $notas->links() }}</div></section>
@endsection
