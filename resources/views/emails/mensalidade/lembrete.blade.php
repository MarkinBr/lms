<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4CAF50; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
        .footer { background: #333; color: white; padding: 15px; text-align: center; font-size: 12px; border-radius: 0 0 5px 5px; }
        .button { background: #4CAF50; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 15px; }
        .info-box { background: white; border-left: 4px solid #4CAF50; padding: 15px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>🔔 Lembrete de Mensalidade</h2>
        </div>
        
        <div class="content">
            <p>Olá, <strong>{{ $aluno }}</strong>!</p>
            
            <p>Este é um lembrete amigável sobre sua mensalidade:</p>
            
            <div class="info-box">
                <p><strong>💰 Valor:</strong> {{ $mensalidade->valor_formatado }}</p>
                <p><strong>📅 Vencimento:</strong> {{ $mensalidade->data_vencimento->format('d/m/Y') }}</p>
                @if($diasParaVencimento > 0)
                    <p><strong>⏰ Faltam:</strong> {{ $diasParaVencimento }} dias</p>
                @else
                    <p style="color: #ff6b6b;"><strong>⚠️ VENCE HOJE!</strong></p>
                @endif
            </div>
            
            <p>Por favor, efetue o pagamento antes do vencimento para evitar multas e juros.</p>
            
            @if($mensalidade->pix_copia_cola)
                <p><strong>Pague com PIX:</strong></p>
                <div style="background: #e8f5e9; padding: 10px; border-radius: 5px; word-break: break-all; font-size: 11px;">
                    {{ $mensalidade->pix_copia_cola }}
                </div>
            @endif
            
            @if($mensalidade->link_boleto)
                <p style="text-align: center;">
                    <a href="{{ $mensalidade->link_boleto }}" class="button">📄 Baixar Boleto</a>
                </p>
            @endif
        </div>
        
        <div class="footer">
            <p>Em caso de dúvidas, entre em contato conosco.</p>
            <p>&copy; {{ date('Y') }} - Todos os direitos reservados</p>
        </div>
    </div>
</body>
</html>