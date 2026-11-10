@extends('secretaria.central.layout')
@section('title', 'Movimentações acadêmicas')
@section('breadcrumb')
    <span aria-hidden="true">/</span><a href="{{ route('secretaria.matriculas.painel') }}">Matrículas e turmas</a>
    <span aria-hidden="true">/</span><span aria-current="page">Movimentações</span>
@endsection
@section('content')
    <div class="sc-intro">
        <div><p class="sc-eyebrow">Rastreabilidade acadêmica</p><h1>Movimentações acadêmicas</h1>
        <p>Histórico de correções de números, novas vinculações e demais operações registradas pela Central.</p></div>
    </div>
    @include('secretaria.central.matriculas.nav', ['secao' => 'movimentacoes'])
    <section class="sc-panel" aria-labelledby="titulo-movimentacoes">
        <div class="sc-panel-heading">
            <h2 id="titulo-movimentacoes">Histórico de atendimentos acadêmicos</h2>
            <p>Registros de auditoria persistidos no servidor. Esta tela é somente para consulta.</p>
            <form action="{{ route('secretaria.matriculas.movimentacoes') }}" method="get" role="search" class="sc-ac-filter" style="grid-template-columns:minmax(170px,1fr) minmax(150px,190px) auto">
                <label class="sc-sr-only" for="mov-busca">Pesquisar aluno, matrícula ou motivo</label>
                <input id="mov-busca" type="search" name="busca" maxlength="150" value="{{ $busca }}" placeholder="Aluno, número ou motivo">
                <label class="sc-sr-only" for="mov-tipo">Filtrar tipo de movimentação</label>
                <select id="mov-tipo" name="tipo">
                    @foreach ([''=>'Todas as movimentações','correcao_numero'=>'Correção de número','vinculo_turma'=>'Vinculação de turma','transferencia_turma'=>'Transferência de turma','situacao_matricula'=>'Alteração de situação'] as $chave=>$rotulo)
                        <option value="{{ $chave }}" @selected($tipo === $chave)>{{ $rotulo }}</option>
                    @endforeach
                </select>
                <button class="sc-button sc-primary" type="submit">Buscar</button>
            </form>
            @if($busca!=='' || $tipo!=='')<a class="sc-text-link" href="{{ route('secretaria.matriculas.movimentacoes') }}">Limpar filtros</a>@endif
        </div>
        <div class="sc-list-label"><span>{{ number_format($historico->total(),0,',','.') }} movimentações encontradas</span></div>
        <div class="sc-ac-table-wrap">
            <table class="sc-ac-history">
                <thead><tr><th>Data</th><th>Aluno / matrícula</th><th>Movimentação</th><th>Justificativa</th><th>Responsável</th></tr></thead>
                <tbody>
                    @forelse($historico as $evento)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($evento->created_at)->format('d/m/Y H:i') }}</td>
                            <td><strong>{{ $evento->aluno ?? 'Não identificado' }}</strong>
                                <small><a class="sc-ac-footer-link sc-ac-mono" href="{{ route('secretaria.matriculas.show', $evento->matricula_id) }}">{{ $evento->numero_matricula ?: 'Sem número' }}</a></small></td>
                            <td><strong>{{ [
                                'correcao_numero'=>'Correção de número','vinculo_turma'=>'Vinculação de turma',
                                'transferencia_turma'=>'Transferência de turma','situacao_matricula'=>'Alteração de situação',
                            ][$evento->tipo] ?? str_replace('_',' ',$evento->tipo) }}</strong>
                                <small>{{ $evento->valor_anterior ?? '—' }} → {{ $evento->valor_novo ?? '—' }}</small></td>
                            <td>{{ $evento->motivo }}</td>
                            <td>{{ $evento->responsavel ?? 'Conta indisponível' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">Nenhuma movimentação encontrada para os filtros informados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="sc-panel-body">{{ $historico->links() }}</div>
    </section>
@endsection
