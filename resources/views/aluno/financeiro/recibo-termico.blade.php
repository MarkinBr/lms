<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recibo #{{ str_pad($mensalidade->id, 6, '0', STR_PAD_LEFT) }}</title>
    <style>
        @media print {
            @page { margin: 0; size: 80mm auto; }
            body { margin: 0; padding: 5px; }
            .no-print { display: none !important; }
        }
        body {
            font-family: 'Courier New', Courier, monospace; /* Fonte monoespaçada estilo cupom */
            font-size: 12px;
            max-width: 80mm; /* Largura padrão de impressora térmica */
            margin: 0 auto;
            background: #fff;
            color: #000;
            padding: 10px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .dashed-line { border-bottom: 1px dashed #000; margin: 10px 0; }
        
        .header { margin-bottom: 15px; }
        .logo { font-size: 16px; font-weight: bold; margin-bottom: 5px; text-transform: uppercase; }
        
        .info-row { display: flex; justify-content: space-between; margin-bottom: 4px; }
        .label { font-weight: bold; }
        
        .total-box { 
            border: 2px solid #000; 
            padding: 8px; 
            margin: 10px 0; 
            text-align: center; 
            font-weight: bold; 
            font-size: 14px; 
        }
        
        .footer { font-size: 10px; margin-top: 20px; text-align: center; }
        
        /* Botão de impressão visível apenas na tela */
        .btn-print {
            display: block;
            width: 100%;
            padding: 12px;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            text-transform: uppercase;
            cursor: pointer;
            margin-bottom: 20px;
            font-family: sans-serif;
        }
        .btn-print:hover { background: #1d4ed8; }
    </style>
</head>
<body onload="window.print()">

    <button onclick="window.print()" class="btn-print no-print">
        🖨️ Imprimir Recibo
    </button>

    <div class="header text-center">
        <div class="logo">CETS - Educacional</div>
        <div>CNPJ: 00.000.000/0001-00</div>
        <div>Rua Exemplo, 123 - Centro</div>
        <div>Tel: (21) 9999-9999</div>
    </div>

    <div class="dashed-line"></div>

    <div class="text-center font-bold" style="font-size: 14px; margin-bottom: 10px;">
        RECIBO DE PAGAMENTO
    </div>

    <div class="info-row">
        <span class="label">Recibo Nº:</span>
        <span>{{ str_pad($mensalidade->id, 6, '0', STR_PAD_LEFT) }}</span>
    </div>
    <div class="info-row">
        <span class="label">Data:</span>
        <span>{{ \Carbon\Carbon::parse($mensalidade->data_pagamento)->format('d/m/Y H:i') }}</span>
    </div>
    <div class="info-row">
        <span class="label">Matrícula:</span>
        <span>{{ $mensalidade->matricula->id ?? '---' }}</span>
    </div>

    <div class="dashed-line"></div>

    <div style="margin-bottom: 5px;">
        <span class="label">Aluno:</span><br>
        {{ \Str::upper($mensalidade->matricula->user->name ?? $mensalidade->matricula->aluno->nome ?? 'Aluno') }}
    </div>

    <div style="margin-bottom: 5px;">
        <span class="label">Referente a:</span><br>
        {{ $mensalidade->matricula->curso->titulo ?? 'Curso' }}<br>
        <small>{{ $mensalidade->observacao ?? 'Mensalidade' }}</small>
    </div>

    <div class="total-box">
        TOTAL PAGO: R$ {{ number_format($mensalidade->valor, 2, ',', '.') }}
    </div>

    <div class="info-row">
        <span class="label">Forma Pagto:</span>
        <span style="text-transform: uppercase;">
            {{ $mensalidade->metodo_pagamento == 'pix' ? 'PIX' : ($mensalidade->metodo_pagamento == 'boleto' ? 'Boleto' : 'Outros') }}
        </span>
    </div>

    @if($mensalidade->transacao_id)
    <div class="info-row">
        <span class="label">Transação (ID):</span>
        <span style="font-size: 10px;">{{ substr($mensalidade->transacao_id, 0, 15) }}...</span>
    </div>
    @endif

    <div class="dashed-line"></div>

    <div class="footer">
        <p>Este documento comprova o pagamento<br>para todos os fins legais.</p>
        <br>
        <p style="font-size: 9px;">Hash Autenticação:<br>{{ substr(md5($mensalidade->id . $mensalidade->updated_at), 0, 20) }}</p>
        <p>www.cets.com.br</p>
    </div>

</body>
</html>