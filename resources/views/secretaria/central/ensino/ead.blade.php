@extends('secretaria.central.layout')
@section('title','Frequência EAD e participação')
@section('breadcrumb')<span aria-hidden="true">/</span><a href="{{ route('secretaria.ensino.painel') }}">Ensino e aprendizagem</a><span aria-hidden="true">/</span><span>Frequência EAD</span>@endsection
@section('content')
<div class="sc-intro"><div><p class="sc-eyebrow">Acompanhamento digital</p><h1>Frequência EAD · participação</h1><p>Analise os registros de participação no ambiente virtual sem confundi-los com a frequência presencial oficial.</p></div></div>
@include('secretaria.central.ensino.nav',['secao'=>'ead'])
<div class="sc-en-banner"><strong>Indicadores, não prova de atenção:</strong> o LMS atual registra progresso e tempo informado pelo cliente. Reproduzir ou concluir uma aula não garante que o conteúdo foi efetivamente acompanhado. Estes dados não são convertidos em presença oficial.</div>
<section class="sc-panel"><div class="sc-panel-heading"><h2>Atividade registrada nas aulas</h2>
<form class="sc-en-filters" method="get" action="{{ route('secretaria.ensino.ead') }}"><input type="search" name="busca" value="{{ $busca }}" aria-label="Pesquisar aluno" placeholder="Aluno..."><button class="sc-button sc-primary">Buscar</button></form>
</div><div class="sc-en-tablewrap"><table class="sc-en-table"><thead><tr><th>Aluno</th><th>Aula</th><th>Progresso técnico</th><th>Tempo informado</th><th>Conclusão registrada</th></tr></thead><tbody>
@forelse($registros as $registro)<tr><td><strong>{{ $registro->user?->name??'Aluno não encontrado' }}</strong></td><td>{{ $registro->aula?->titulo??'Aula não encontrada' }}</td>
<td>{{ $registro->progresso_porcentagem??0 }}%</td><td>{{ (int)($registro->tempo_estudo_minutos??0) }} min (estimado)</td>
<td><span class="sc-en-badge {{ $registro->concluida?'':'warn' }}">{{ $registro->concluida?'Registrada':'Pendente' }}</span></td></tr>
@empty<tr><td colspan="5">Nenhum progresso localizado.</td></tr>@endforelse</tbody></table></div><div class="sc-panel-body">{{ $registros->links() }}</div></section>
<div class="sc-en-grid" style="margin-top:20px">
<section class="sc-side-card"><h2>Vídeos</h2><p>Futuramente, usar segmentos únicos de reprodução confirmados pelo servidor, em vez de apenas posição de reprodução.</p></section>
<section class="sc-side-card"><h2>Leituras e materiais</h2><p>Tempo de leitura deve ser registrado separadamente, com confirmação por atividade, sem monitoramento invasivo.</p></section>
<section class="sc-side-card"><h2>Aprendizagem</h2><p>Quizzes, provas e simulados permitem verificar compreensão, mas não substituem presença oficial sem regra pedagógica aprovada.</p></section>
</div>
@endsection
