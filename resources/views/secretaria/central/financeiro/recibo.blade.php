<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Recibo CETSADP #{{ $mensalidade->id }}</title><style>
*{box-sizing:border-box}body{margin:0;background:#f2f5f8;font-family:Arial,sans-serif;color:#102f50}
.tools{background:#0a2e54;padding:15px;text-align:center}.tools button{border:0;background:#c39650;color:#0a2645;padding:11px 17px;border-radius:8px;font-weight:bold;cursor:pointer}
.tools a{color:#fff;margin-left:15px}.paper{background:#fff;padding:18px;width:78mm;margin:20px auto;border:1px solid #ddd}
.paper h1{font-size:16px;text-align:center;margin:0 0 3px}.paper .sub{text-align:center;font-size:10px;color:#536c80}
.separator{border-top:1px dashed #667;margin:13px 0}.line{display:flex;justify-content:space-between;gap:5px;font-size:11px;margin:7px 0}
.line span:first-child{color:#566e7b}.total{text-align:center;border:2px solid #123d61;padding:12px;margin:15px 0}
.total strong{font-size:19px}.qr{text-align:center;margin-top:10px}.qr svg{max-width:95px;height:auto}
small{font-size:9px}.bottom{text-align:center;font-size:9px;color:#5d6b77;overflow-wrap:anywhere}
@media print{@page{size:80mm auto;margin:2mm}body{background:#fff}.tools{display:none}.paper{border:0;padding:1mm;margin:0 auto;width:76mm}}
</style></head><body>
<div class="tools"><button onclick="window.print()">Imprimir recibo 80 mm</button><a href="{{ route('secretaria.financeiro.painel') }}">Voltar ao Financeiro</a></div>
<div class="paper"><h1>CETSADP</h1><div class="sub">Secretaria Acadêmica · IEADP</div>
<div class="separator"></div><p style="text-align:center;font-weight:bold">RECIBO DE PAGAMENTO</p>
<div class="line"><span>Recibo</span><strong>#{{ $mensalidade->id }}</strong></div>
<div class="line"><span>Data</span><strong>{{ $mensalidade->data_pagamento?->format('d/m/Y') }}</strong></div>
<div class="line"><span>Aluno</span><strong>{{ $mensalidade->matricula?->user?->name }}</strong></div>
<div class="line"><span>Matrícula</span><strong>{{ $mensalidade->matricula?->numero_matricula }}</strong></div>
<div class="line"><span>Curso</span><strong>{{ $mensalidade->matricula?->curso?->titulo }}</strong></div>
<div class="line"><span>Competência</span><strong>{{ $mensalidade->data_vencimento?->format('m/Y') }}</strong></div>
<div class="line"><span>Meio</span><strong>{{ strtoupper((string)$mensalidade->metodo_pagamento) }}</strong></div>
<div class="separator"></div><div class="total">VALOR RECEBIDO<br><strong>R$ {{ number_format((float)($mensalidade->valor_pago??$mensalidade->valor),2,',','.') }}</strong></div>
<div class="qr">{!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(100)->margin(1)->generate($verificacao) !!}<br><small>Escaneie para verificar o recibo</small></div>
<div class="separator"></div><div class="bottom">CÓDIGO DE VERIFICAÇÃO<br>{{ $mensalidade->codigo_validacao }}<br><br>
A página de verificação consulta se a mensalidade permanece paga. O QR do recibo é diferente do QR PIX.</div></div>
</body></html>
