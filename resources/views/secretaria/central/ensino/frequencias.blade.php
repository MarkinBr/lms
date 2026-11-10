@extends('secretaria.central.layout')
@section('title','Frequência presencial')
@section('breadcrumb')<span aria-hidden="true">/</span><a href="{{ route('secretaria.ensino.painel') }}">Ensino e aprendizagem</a><span aria-hidden="true">/</span><span>Frequência presencial</span>@endsection
@section('content')
<div class="sc-intro"><div><p class="sc-eyebrow">Frequência oficial</p><h1>Frequência presencial</h1><p>Chamadas registradas por turma, disciplina e data. Apenas chamadas concluídas integram os percentuais oficiais.</p></div>
<a class="sc-button sc-primary" href="{{ route('filament.secretario.resources.chamada-frequencias.index') }}">Gerenciar chamadas</a></div>
@include('secretaria.central.ensino.nav',['secao'=>'frequencias'])
<section class="sc-panel"><div class="sc-panel-heading"><h2>Chamadas e presenças</h2>
<form class="sc-en-filters" method="get" action="{{ route('secretaria.ensino.frequencias') }}"><input type="search" name="busca" value="{{ $busca }}" aria-label="Filtrar turma" placeholder="Nome da turma"><button class="sc-button sc-primary">Filtrar</button></form>
</div><div class="sc-en-tablewrap"><table class="sc-en-table"><thead><tr><th>Data</th><th>Turma</th><th>Disciplina</th><th>Registros</th><th>Situação</th></tr></thead><tbody>
@forelse($chamadas as $chamada)<tr><td>{{ $chamada->data_aula?->format('d/m/Y') }}</td><td>{{ $chamada->turma?->nome??'Turma não informada' }}</td>
<td>{{ $chamada->disciplina?->nome??'Não informada' }}</td><td>{{ $chamada->frequencias_count }}</td><td><span class="sc-en-badge {{ $chamada->status==='concluida'?'':'warn' }}">{{ \App\Models\ChamadaFrequencia::STATUS_LABELS[$chamada->status]??$chamada->status }}</span></td></tr>
@empty<tr><td colspan="5">Nenhuma chamada cadastrada.</td></tr>@endforelse</tbody></table></div><div class="sc-panel-body">{{ $chamadas->links() }}</div></section>
<p class="sc-support-note">Registros de presença, falta e falta justificada permanecem no sistema oficial de chamadas. A participação EAD é acompanhada em aba separada.</p>
@endsection
