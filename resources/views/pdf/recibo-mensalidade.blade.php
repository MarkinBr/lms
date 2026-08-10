<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Pagamento</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            padding: 30px;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #003D7A;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #003D7A;
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 11px;
            color: #666;
        }
        
        .recibo-numero {
            background-color: #003D7A;
            color: white;
            padding: 10px;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 30px;
            border-radius: 5px;
        }
        
        .info-box {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f5f5f5;
            border-left: 4px solid #003D7A;
        }
        
        .info-box h3 {
            color: #003D7A;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 5px 0;
            border-bottom: 1px dotted #ddd;
        }
        
        .info-label {
            font-weight: bold;
            color: #666;
        }
        
        .info-value {
            color: #333;
        }
        
        .valor-box {
            background-color: #e8f5e9;
            border: 2px solid #4caf50;
            padding: 20px;
            text-align: center;
            margin: 30px 0;
            border-radius: 10px;
        }
        
        .valor-box h2 {
            color: #4caf50;
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .valor-box p {
            font-size: 12px;
            color: #666;
        }
        
        .observacao {
            margin-top: 20px;
            padding: 15px;
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        
        .assinatura {
            margin-top: 50px;
            text-align: center;
        }
        
        .assinatura-linha {
            border-top: 1px solid #333;
            width: 300px;
            margin: 0 auto 10px;
            padding-top: 5px;
        }
        
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        
        .selo {
            position: absolute;
            top: 30px;
            right: 30px;
            background-color: #4caf50;
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: bold;
            transform: rotate(15deg);
        }
        
        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        
        table th, table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        table th {
            background-color: #003D7A;
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="selo">✓ PAGO</div>
    
    <div class="header">
        <h1>RECIBO DE PAGAMENTO</h1>
        <p>Centro Educacional Teológico e Social - CETSADP</p>
        <p>CNPJ: XX.XXX.XXX/XXXX-XX | Telefone: (21) XXXX-XXXX</p>
        <p>Endereço: Sua Rua, Nº 123 - Cidade - Estado</p>
    </div>
    
    <div class="recibo-numero">
        RECIBO Nº {{ str_pad($mensalidade->id, 6, '0', STR_PAD_LEFT) }} / {{ date('Y') }}
    </div>
    
    <div class="info-box">
        <h3>📋 Dados do Pagador</h3>
        <div class="info-row">
            <span class="info-label">Nome:</span>
            <span class="info-value">{{ $mensalidade->matricula->user->name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">CPF:</span>
            <span class="info-value">{{ $mensalidade->matricula->user->cpf ?? 'Não informado' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Matrícula:</span>
            <span class="info-value">{{ $mensalidade->matricula->numero_matricula }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Curso:</span>
            <span class="info-value">{{ $mensalidade->matricula->curso->titulo }}</span>
        </div>
    </div>
    
    <div class="info-box">
        <h3>💰 Dados do Pagamento</h3>
        <div class="info-row">
            <span class="info-label">Referente a:</span>
            <span class="info-value">Mensalidade - Vencimento: {{ $mensalidade->data_vencimento->format('d/m/Y') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Data do Pagamento:</span>
            <span class="info-value">{{ $mensalidade->data_pagamento->format('d/m/Y') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Forma de Pagamento:</span>
            <span class="info-value">
                @switch($mensalidade->metodo_pagamento)
                    @case('dinheiro') 💵 Dinheiro @break
                    @case('pix') 📱 PIX @break
                    @case('cartao_credito') 💳 Cartão de Crédito @break
                    @case('cartao_debito') 💳 Cartão de Débito @break
                    @case('boleto') 📄 Boleto Bancário @break
                    @case('transferencia') 🏦 Transferência Bancária @break
                    @default {{ $mensalidade->metodo_pagamento }}
                @endswitch
            </span>
        </div>
    </div>
    
    <div class="valor-box">
        <h2>R$ {{ number_format($mensalidade->valor, 2, ',', '.') }}</h2>
        <p>({{ extenso($mensalidade->valor) }})</p>
    </div>
    
    @if($mensalidade->observacao)
    <div class="observacao">
        <strong>📝 Observação:</strong><br>
        {{ $mensalidade->observacao }}
    </div>
    @endif
    
    <div class="assinatura">
        <div class="assinatura-linha">
            {{ config('app.name', 'CETSADP') }}
        </div>
        <p><strong>Secretaria Escolar</strong></p>
        <p style="font-size: 10px; color: #999;">
            Documento emitido eletronicamente em {{ now()->format('d/m/Y H:i:s') }}
        </p>
    </div>
    
    <div class="footer">
        <p>Este recibo é válido como comprovante de pagamento.</p>
        <p>Em caso de dúvidas, entre em contato com a secretaria.</p>
        <p><strong>Guarde este documento para futuras referências.</strong></p>
    </div>
</body>
</html>