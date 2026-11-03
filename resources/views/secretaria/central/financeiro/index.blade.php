@extends('secretaria.central.layout')
@section('title','Financeiro')
@section('breadcrumb')<span aria-hidden="true">/</span><span aria-current="page">Financeiro</span>@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('css/secretaria-financeiro.css') }}?v={{ filemtime(public_path('css/secretaria-financeiro.css')) }}">
@endpush
@section('content')
<div class="sc-intro"><div><p class="sc-eyebrow">Secretaria acadêmica</p><h1>Financeiro</h1>
<p>Consulte mensalidades, gere PIX ou boleto pelo Mercado Pago, confira recibos e programe comunicações.</p></div>
<a class="fin-button" href="{{ route('secretaria.financeiro.relatorio', ['inicio'=>today()->startOfMonth()->toDateString(), 'fim'=>today()->toDateString()]) }}" target="_blank" rel="noopener noreferrer">Imprimir relatório A4 ↗</a></div>
<div class="fin-tabs" aria-label="Navegação horizontal do Financeiro">
<a class="active" href="#visao">Visão do dia</a><a href="#mensalidades">Alunos e mensalidades</a><a href="#comunicacoes">Mensagens agendadas</a><a href="#relatorios">Relatórios impressos</a>
<a href="{{ route('secretaria.documentos.painel') }}">Documentos e solicitações</a>
</div>
<div class="fin-notice">
<strong>Emissão real pelo Mercado Pago:</strong>
@if(config('financeiro.habilitar_emissao_mercadopago_secretaria',false))
Integração operacional habilitada. Confira os dados bancários, o valor e o destinatário antes de gerar cada cobrança. O recebimento só será reconhecido após o webhook ou conciliação bancária.
@else
Integração operacional desativada por segurança. O administrador deverá homologar o ambiente e habilitar a emissão pelo servidor. Nenhuma cobrança será gerada nesta instalação até isso acontecer.
@endif
</div>
<dl class="fin-metrics" id="visao"><div class="fin-metric"><span>Recebido no mês</span><strong>R$ {{ number_format($totais['recebido_mes'],2,',','.') }}</strong></div>
<div class="fin-metric"><span>Valor em aberto</span><strong>R$ {{ number_format($totais['em_aberto'],2,',','.') }}</strong></div>
<div class="fin-metric"><span>Mensalidades vencidas</span><strong>{{ $totais['vencidas'] }}</strong></div>
<div class="fin-metric"><span>Mensagens agendadas</span><strong>{{ $totais['agendamentos'] }}</strong></div></dl>
<section class="fin-card" id="mensalidades"><div class="fin-head"><div><h2>Alunos, mensalidades e cobranças</h2><p>As ações são vinculadas à mensalidade real, sem alterar a matrícula de acesso.</p></div></div>
<div class="fin-body"><form class="fin-filters" method="get" action="{{ route('secretaria.financeiro.painel') }}">
<input name="busca" type="search" aria-label="Nome do aluno" value="{{ $busca }}" placeholder="Pesquisar aluno...">
<select name="situacao" aria-label="Situação"><option value="">Todas as situações</option>
@foreach(['Pago'=>'Pago','Pendente'=>'Pendente','Atrasado'=>'Atrasado'] as $valor=>$rotulo)
<option value="{{ $valor }}" @selected($situacao===$valor)>{{ $rotulo }}</option>@endforeach</select>
<button class="fin-button fin-primary" type="submit">Filtrar</button></form></div>
<div class="fin-table-wrap"><table class="fin-table"><thead><tr><th>Aluno e curso</th><th>Vencimento</th><th>Valor</th><th>Situação</th><th>Operações seguras</th></tr></thead><tbody>
@forelse($mensalidades as $m)
<tr><td><strong>{{ $m->matricula?->user?->name ?? 'Aluno não informado' }}</strong><small>{{ $m->matricula?->numero_matricula }} · {{ $m->matricula?->curso?->titulo }}</small></td>
<td>{{ $m->data_vencimento?->format('d/m/Y') }}</td>
<td><strong>R$ {{ number_format((float)$m->valor,2,',','.') }}</strong>
@if($m->status==='Pago')<small>Recebido: R$ {{ number_format((float)($m->valor_pago??$m->valor),2,',','.') }}</small>@endif</td>
<td><span class="fin-badge {{ $m->status==='Pago'?'ok':($m->status==='Atrasado'?'late':'warn') }}">{{ $m->status }}</span></td>
<td style="min-width:270px">
@if($m->status==='Pago')
<a href="{{ route('secretaria.financeiro.recibo',$m) }}" class="fin-button" target="_blank" rel="noopener noreferrer">▤ Recibo térmico + QR</a>
<a class="fin-button" href="{{ route('recibo.publico.form',$m->codigo_validacao) }}" target="_blank" rel="noopener noreferrer">Verificar</a>
@elseif($m->status!=='Cancelado')
<div class="fin-actions" style="justify-content:flex-start">
<form method="post" action="{{ route('secretaria.financeiro.pix',$m) }}">@csrf
<label><input type="checkbox" name="conferi_cobranca" value="1" required> Conferi</label>
<button class="fin-button fin-primary" type="submit">Gerar PIX</button></form>
<form method="post" action="{{ route('secretaria.financeiro.boleto',$m) }}">@csrf
<label><input type="checkbox" name="conferi_cobranca" value="1" required> Conferi</label>
<button class="fin-button" type="submit">Gerar boleto</button></form></div>
@if($m->pix_copia_cola)
<div style="margin-top:10px"><small>PIX copia e cola (aguardando pagamento):</small><code class="fin-code">{{ $m->pix_copia_cola }}</code>
@if($m->pix_qr_base64)<img class="fin-qr" src="data:image/png;base64,{{ $m->pix_qr_base64 }}" alt="QR Code PIX retornado pelo Mercado Pago">@endif
</div>@endif
@if($m->link_boleto && str_starts_with($m->link_boleto,'https://'))
<a class="fin-button" style="margin-top:8px" href="{{ $m->link_boleto }}" target="_blank" rel="noopener noreferrer">Abrir boleto Mercado Pago ↗</a>
@endif
@endif
<details class="fin-detail"><summary>Programar e-mail ou WhatsApp</summary><div class="fin-body">
<form class="fin-form" method="post" action="{{ route('secretaria.financeiro.agendar',$m) }}">@csrf
<div><label>Canal</label><select name="canal" required><option value="email">E-mail</option><option value="whatsapp">WhatsApp</option></select></div>
<div><label>Tipo</label><select name="modelo" required>
@if($m->status==='Pago')<option value="recibo">Recibo e confirmação</option>
@else
<option value="lembrete">Lembrete</option><option value="cobranca">Cobrança cordial</option>
@if($m->pix_copia_cola)<option value="pix">PIX emitido</option>@endif
@if($m->link_boleto)<option value="boleto">Boleto emitido</option>@endif
@endif</select></div>
<div class="wide"><label>Data e hora de envio</label><input type="datetime-local" name="agendado_para" required></div>
<div class="wide"><label style="font-size:11px"><input type="checkbox" name="confirmou_contato" value="1" required> Confirmei a autorização de contato e os dados de destinatário.</label></div>
<div class="wide"><button class="fin-button fin-primary" type="submit">Programar comunicação</button></div>
</form></div></details></td></tr>
@empty<tr><td colspan="5">Nenhuma mensalidade encontrada com os filtros selecionados.</td></tr>@endforelse
</tbody></table></div><div class="fin-paging">{{ $mensalidades->links() }}</div></section>
<div class="fin-two">
<section class="fin-card" id="comunicacoes"><div class="fin-head"><div><h2>Envios programados e histórico</h2><p>E-mail com template institucional e recibo PDF; WhatsApp por mensagem oficial.</p></div></div>
<div class="fin-body"><ul class="fin-events">
@forelse($envios as $envio)
<li><div style="display:flex;justify-content:space-between;gap:8px"><div><strong>{{ ucfirst($envio->modelo) }} · {{ strtoupper($envio->canal) }}</strong><small>{{ $envio->agendado_para?->format('d/m/Y H:i') }} · {{ $envio->status }} · {{ $envio->mensalidade_id }}</small></div>
@if($envio->status==='agendado')<form method="post" action="{{ route('secretaria.financeiro.envio.cancelar',$envio) }}">@csrf
<button class="fin-button" type="submit">Cancelar</button></form>@endif</div></li>
@empty<li>Sem mensagens programadas.</li>@endforelse</ul></div></section>
<aside class="fin-card" id="relatorios"><div class="fin-head"><div><h2>Relatório impresso A4</h2><p>Recebimentos reais confirmados por período, valor e meio.</p></div></div>
<div class="fin-body"><form class="fin-form" action="{{ route('secretaria.financeiro.relatorio') }}" method="get" target="_blank">
<div><label for="fin-inicio">Início</label><input id="fin-inicio" type="date" name="inicio" value="{{ today()->startOfMonth()->toDateString() }}" required></div>
<div><label for="fin-fim">Fim</label><input id="fin-fim" type="date" name="fim" value="{{ today()->toDateString() }}" required></div>
<div class="wide"><button class="fin-button fin-primary" type="submit">Gerar relatório PDF A4</button></div></form>
<p style="font-size:11px;color:#6f8394;margin-top:15px">Relatórios não incluem prints em análise; valide totais com os extratos, os recebimentos em espécie e a movimentação financeira.</p></div></aside>
</div>
@endsection
