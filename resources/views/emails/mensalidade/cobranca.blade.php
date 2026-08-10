<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #f44336; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
        .footer { background: #333; color: white; padding: 15px; text-align: center; font-size: 12px; border-radius: 0 0 5px 5px; }
        .button { background: #f44336; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 15px; }
        .alert-box { background: #fff3cd; border-left: 4px solid #ff9800; padding: 15px; margin: 20px 0; }
        .info-box { background: white; border-left: 4px solid #f44336; padding: 15px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>🔴 Mensalidade em Atraso</h2>
        </div>
        
        <div class="content">
            <p>Olá, <strong>{{ $aluno }}</strong>,</p>
            
            <div class="alert-box">
                <p><strong>⚠️ ATENÇÃO:</strong> Sua mensalidade está atrasada!</p>
            </div>
            
            <div class="info-box">
                <p><strong>📅 Vencimento:</strong> {{ $mensalidade->data_vencimento->format('d/m/Y') }}</p>
                <p><strong>⏱️ Dias de atraso:</strong> {{ $mensalidade->dias_atraso }} dias</p>
                <p><strong>💵 Valor original:</strong> {{ $mensalidade->valor_formatado }}</p>
                <p><strong>💰 Valor com multa:</strong> <span style="color: #f44336; font-size: 18px;">{{ $mensalidade->valor_com_multa_formatado }}</span></p>
            </div>
            
            <p>Por favor, regularize sua situação o quanto antes para evitar problemas com sua matrícula.</p>
            
            <p><strong>Entre em contato com a secretaria para negociar o pagamento.</strong></p>
            
            @if($mensalidade->pix_copia_cola)
                <p><strong>Pague agora com PIX:</strong></p>
                <div style="background: #ffebee; padding: 10px; border-radius: 5px; word-break: break-all; font-size: 11px;">
                    {{ $mensalidade->pix_copia_cola }}
                </div>
            @endif
        </div>
        
        <div class="footer">
            <p>Secretaria - Telefone: (XX) XXXXX-XXXX</p>
            <p>&copy; {{ date('Y') }} - Todos os direitos reservados</p>
        </div>
    </div>
</body>
</html>