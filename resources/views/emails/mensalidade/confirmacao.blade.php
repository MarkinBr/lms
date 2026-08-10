<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4CAF50; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; text-align: center; }
        .footer { background: #333; color: white; padding: 15px; text-align: center; font-size: 12px; border-radius: 0 0 5px 5px; }
        .success-box { background: #e8f5e9; border: 2px solid #4CAF50; padding: 20px; border-radius: 10px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>✅ Pagamento Confirmado!</h2>
        </div>
        
        <div class="content">
            <p>Olá, <strong>{{ $aluno }}</strong>!</p>
            
            <div class="success-box">
                <h3 style="color: #4CAF50; margin-top: 0;">🎉 Parabéns!</h3>
                <p>Confirmamos o recebimento do seu pagamento!</p>
            </div>
            
            <div style="background: white; padding: 20px; border-radius: 10px; margin: 20px 0;">
                <p><strong>💰 Valor Pago:</strong> <span style="font-size: 24px; color: #4CAF50;">{{ $mensalidade->valor_formatado }}</span></p>
                <p><strong>📅 Data do Pagamento:</strong> {{ $mensalidade->data_pagamento->format('d/m/Y') }}</p>
                <p><strong>💳 Forma de Pagamento:</strong> {{ $mensalidade->metodo_pagamento }}</p>
            </div>
            
            <p>Seu comprovante está anexado neste email.</p>
            
            <p style="font-size: 14px; color: #666;">
                Obrigado por manter sua mensalidade em dia! ❤️
            </p>
        </div>
        
        <div class="footer">
            <p>Continue com a gente!</p>
            <p>&copy; {{ date('Y') }} - Todos os direitos reservados</p>
        </div>
    </div>
</body>
</html>
