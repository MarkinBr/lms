@extends('secretaria.central.layout')
@section('title','Conferência documental')
@section('breadcrumb')<span aria-hidden="true">/</span><a href="{{ route('secretaria.documentos.painel') }}">Documentos</a><span aria-hidden="true">/</span><span>Documentação</span>@endsection
@section('content')
<div class="sc-intro"><div><p class="sc-eyebrow">Documentos da inscrição</p><h1>Conferência documental</h1><p>Documentos recebidos dos candidatos e alunos, com prévia privada obrigatória antes da decisão.</p></div></div>
@include('secretaria.central.documentos.nav',['secao'=>'documentacao'])
<div class="sc-doc-notice">Os arquivos são carregados de armazenamento privado. Conferir um arquivo não significa validar sua autenticidade. Documentos rejeitados conservam justificativa e histórico.</div>
<section class="sc-panel"><div class="sc-panel-heading"><h2>Arquivos recebidos</h2>
<div class="sc-doc-actions" style="margin-top:12px">@foreach([''=>'Todos','pendentes'=>'Pendentes','aprovados'=>'Aprovados','rejeitados'=>'Rejeitados'] as $k=>$rotulo)
<a class="sc-button sc-button-small" href="{{ route('secretaria.documentos.documentacao',['situacao'=>$k]) }}">{{ $rotulo }}</a>@endforeach</div></div>
<div class="sc-doc-tablewrap"><table class="sc-doc-table"><thead><tr><th>Estudante / inscrição</th><th>Tipo</th><th>Arquivo</th><th>Situação</th><th>Prévia</th></tr></thead><tbody>
@forelse($documentos as $documento)<tr>
<td><strong>{{ $documento->application?->nome_completo??'Estudante não informado' }}</strong><small>{{ $documento->created_at?->format('d/m/Y H:i') }}</small></td>
<td>{{ $documento->tipo_label }}</td><td>{{ $documento->nome_original }}</td>
<td><span class="sc-doc-badge {{ $documento->validado?'ok':($documento->validado_por_id?'bad':'warn') }}">{{ $documento->validado?'Aprovado':($documento->validado_por_id?'Rejeitado':'Pendente') }}</span></td>
<td><a class="sc-button sc-button-small" href="{{ route('secretaria.documentos.documentacao.conferir',$documento) }}">Conferir →</a></td>
</tr>@empty<tr><td colspan="5">Nenhum documento encontrado.</td></tr>@endforelse
</tbody></table></div><div class="sc-panel-body">{{ $documentos->links() }}</div></section>
@endsection
