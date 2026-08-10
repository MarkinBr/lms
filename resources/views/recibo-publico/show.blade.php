<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Recibo {{ $mensalidade->codigo_validacao }} — CETSADP</title>
  <style>
    body{font-family:system-ui,sans-serif;background:#f8fafc;padding:24px;color:#1e293b;margin:0}
    .recibo{max-width:680px;margin:0 auto;background:#fff;padding:48px;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,.06)}
    .header{text-align:center;border-bottom:3px solid #003D7A;padding-bottom:24px;margin-bottom:32px}
    .header h1{color:#003D7A;margin:0 0 4px;font-size:22px}
    .header p{margin:2px 0;font-size:12px;color:#64748b}
    .selo{background:#059669;color:#fff;padding:8px 16px;border-radius:20px;display:inline-block;font-weight:700;letter-spacing:2px;font-size:13px;margin:8px 0}
    .info-row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px dotted #e2e8f0}
    .info-row .label{color:#64748b;font-weight:500;font-size:13px}
    .info-row .value{font-weight:600;font-size:13px}
    .valor-box{background:#ecfdf5;border:2px solid #059669;padding:24px;text-align:center;margin:28px 0;border-radius:8px}
    .valor-box .valor{font-size:32px;font-weight:700;color:#059669}
    .valor-box .label{font-size:11px;color:#475569;text-transform:uppercase;letter-spacing:2px;margin-bottom:6px}
    .footer{text-align:center;margin-top:36px;padding-top:20px;border-top:1px solid #e2e8f0;font-size:11px;color:#94a3b8}
    .lgpd{background:#eff6ff;border-left:4px solid #2563eb;padding:14px;margin-top:24px;font-size:12px;color:#1e40af;border-radius:4px}
  </style>
</head>
<body>
  <div class="recibo">
    <div class="header">
      <h1>RECIBO DE PAGAMENTO</h1>
      <p>Centro Educacional Teológico e Social — CETSADP</p>
      <div class="selo">✓ PAGO</div>
      <p>Recibo Nº {{ str_pad($mensalidade->id, 6, '0', STR_PAD_LEFT) }} /
        {{ $mensalidade->data_pagamento->format('Y') }}</p>
    </div>
    <div class="info-row">
      <span class="label">Pagador (parcial):</span>
      <span class="value">{{ $mascarado['nome_mascarado'] }}</span>
    </div>
    <div class="info-row">
      <span class="label">CPF (parcial):</span>
      <span class="value">{{ $mascarado['cpf_mascarado'] }}</span>
    </div>
    <div class="info-row">
      <span class="label">Localização:</span>
      <span class="value">{{ $mascarado['localizacao'] ?: '—' }}</span>
    </div>
    <div class="info-row">
      <span class="label">Matrícula:</span>
      <span class="value">{{ $mensalidade->matricula->numero_matricula }}</span>
    </div>
    <div class="info-row">
      <span class="label">Curso:</span>
      <span class="value">{{ $mensalidade->matricula->curso->titulo ?? '—' }}</span>
    </div>
    <div class="info-row">
      <span class="label">Vencimento:</span>
      <span class="value">{{ $mensalidade->data_vencimento->format('d/m/Y') }}</span>
    </div>
    <div class="info-row">
      <span class="label">Data do Pagamento:</span>
      <span class="value">{{ $mensalidade->data_pagamento->format('d/m/Y') }}</span>
    </div>
    <div class="valor-box">
      <div class="label">Valor Pago</div>
      <div class="valor">R$ {{ number_format($mensalidade->valor_pago ?? $mensalidade->valor, 2, ',', '.') }}</div>
    </div>
    <div class="lgpd">
      ℹ️ Dados pessoais foram mascarados nesta versão pública conforme a LGPD.
      Para visualizar o recibo completo, o aluno deve acessar o portal autenticado.
    </div>
    <div class="footer">
      Código de validação: {{ $mensalidade->codigo_validacao }}<br>
      Documento emitido eletronicamente — {{ now()->format('d/m/Y H:i') }}
    </div>
  </div>
</body>
</html>