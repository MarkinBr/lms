<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8">
<style>
@page{size:A4 portrait;margin:18mm}body{font-family:DejaVu Sans,sans-serif;color:#17344f;font-size:11px}
header{border-bottom:4px solid #b48a44;padding-bottom:15px;margin-bottom:20px}
header h1{font-size:25px;color:#092e55;margin:0}header small{color:#718292}
h2{font-size:18px;text-align:center;color:#0b2f56;margin:24px 0}
table{border-collapse:collapse;width:100%;margin:18px 0}td{padding:11px;border-bottom:1px solid #dce5e9}
td:first-child{width:37%;font-weight:bold;color:#1c4c70}
.total{background:#e8f4ec;border:1px solid #b8d9c4;text-align:center;padding:20px;font-size:20px;font-weight:bold}
footer{text-align:center;border-top:1px solid #dce5e9;padding:14px;font-size:9px;color:#647889;margin-top:32px}
</style></head><body>
<header><h1>CETSADP</h1><small>Centro Educacional, Teológico e Social · IEADP</small></header>
<h2>RECIBO DE PAGAMENTO</h2>
<table>
<tr><td>Recibo</td><td>{{ $mensalidade->codigo_validacao }}</td></tr>
<tr><td>Estudante</td><td>{{ $mensalidade->matricula?->user?->name }}</td></tr>
<tr><td>Matrícula acadêmica</td><td>{{ $mensalidade->matricula?->numero_matricula }}</td></tr>
<tr><td>Curso</td><td>{{ $mensalidade->matricula?->curso?->titulo }}</td></tr>
<tr><td>Vencimento</td><td>{{ $mensalidade->data_vencimento?->format('d/m/Y') }}</td></tr>
<tr><td>Pagamento</td><td>{{ $mensalidade->data_pagamento?->format('d/m/Y') }}</td></tr>
<tr><td>Meio de pagamento</td><td>{{ strtoupper((string)$mensalidade->metodo_pagamento) }}</td></tr>
</table>
<div class="total">VALOR RECEBIDO: R$ {{ number_format((float)($mensalidade->valor_pago??$mensalidade->valor),2,',','.') }}</div>
<p>Este recibo registra pagamento confirmado no sistema. A autenticidade deve ser verificada pelo código acima no endereço oficial CETSADP.</p>
<p style="font-size:10px">Verificação: {{ route('recibo.publico.form',$mensalidade->codigo_validacao) }}</p>
<footer>Gerado em {{ now()->format('d/m/Y H:i') }} · Em caso de cancelamento ou estorno, consulte a situação atual na página de verificação.</footer>
</body></html>
