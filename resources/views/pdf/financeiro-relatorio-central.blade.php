<!doctype html><html lang="pt-BR"><head><meta charset="utf-8">
<style>@page{size:A4 landscape;margin:12mm}body{font-family:DejaVu Sans,sans-serif;color:#263e52;font-size:9px}
header{border-bottom:3px solid #bc8b41;padding-bottom:12px;margin-bottom:17px}h1{font-size:19px;color:#0b3159;margin:0}small{font-size:8px;color:#64788a}
table{width:100%;border-collapse:collapse}thead{background:#123b62;color:#fff}td,th{padding:7px 6px;border-bottom:1px solid #dfe8ee;text-align:left}
td:last-child,th:last-child{text-align:right}.totais{background:#f0f4f7;padding:14px;margin-bottom:17px;font-size:12px}
footer{font-size:8px;color:#6b8190;border-top:1px solid #d2dee7;margin-top:20px;padding-top:9px}</style></head><body>
<header><h1>CETSADP · IEADP — Relatório financeiro</h1><small>Secretaria Acadêmica · Pagamentos confirmados no período de {{ $inicio->format('d/m/Y') }} a {{ $fim->format('d/m/Y') }} (regime de caixa)</small></header>
<div class="totais"><strong>{{ $totais['quantidade'] }} pagamentos confirmados</strong> &nbsp; | &nbsp;
Total recebido: <strong>R$ {{ number_format($totais['total'],2,',','.') }}</strong> &nbsp; | &nbsp;
Em dinheiro: <strong>R$ {{ number_format($totais['dinheiro'],2,',','.') }}</strong> &nbsp; | &nbsp;
Outros meios: <strong>R$ {{ number_format($totais['total']-$totais['dinheiro'],2,',','.') }}</strong></div>
<table><thead><tr><th>Data</th><th>Aluno</th><th>Matrícula acadêmica</th><th>Curso</th><th>Vencimento</th><th>Meio</th><th>Valor pago</th></tr></thead><tbody>
@forelse($mensalidades as $m)
<tr><td>{{ $m->data_pagamento?->format('d/m/Y') }}</td><td>{{ $m->matricula?->user?->name }}</td>
<td>{{ $m->matricula?->numero_matricula }}</td><td>{{ $m->matricula?->curso?->titulo }}</td>
<td>{{ $m->data_vencimento?->format('d/m/Y') }}</td><td>{{ strtoupper((string)$m->metodo_pagamento) }}</td>
<td>R$ {{ number_format((float)($m->valor_pago??$m->valor),2,',','.') }}</td></tr>
@empty<tr><td colspan="7">Nenhum pagamento confirmado neste período.</td></tr>@endforelse
</tbody></table><footer>Emitido para conferência administrativa em {{ now()->format('d/m/Y H:i') }}. Confirme os valores com o extrato do banco, os comprovantes e o fechamento do caixa.</footer>
</body></html>
