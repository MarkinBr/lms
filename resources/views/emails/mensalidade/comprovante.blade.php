<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2196F3; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
        .footer { background: #333; color: white; padding: 15px; text-align: center; font-size: 12px; border-radius: 0 0 5px 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>📄 Comprovante de Pagamento</h2>
        </div>
        
        <div class="content">
            <p>Olá, <strong>{{ $aluno }}</strong>!</p>
            
            <p>Segue anexo o comprovante do seu pagamento.</p>
            
            <div style="background: white; padding: 20px; border-radius: 10px; margin: 20px 0;">
                <p><strong>💰 Valor:</strong> {{ $mensalidade->valor_formatado }}</p>
                <p><strong>📅 Data do Pagamento:</strong> {{ $mensalidade->data_pagamento->format('d/m/Y') }}</p>
            </div>
            
            <p>Guarde este comprovante para seus registros.</p>
        </div>
        
        <div class="footer">
            <p>Em caso de dúvidas, entre em contato conosco.</p>
            <p>&copy; {{ date('Y') }} - Todos os direitos reservados</p>
        </div>
    </div>
</body>
</html>